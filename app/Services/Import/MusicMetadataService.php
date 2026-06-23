<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MusicMetadataService
{
    private const MB_BASE    = 'https://musicbrainz.org/ws/2';
    private const USER_AGENT = 'QuerosenoChords/1.0 (rogersneves@gmail.com)';
    // MusicBrainz allows 1 req/sec; we use 1.3s to stay safely under the limit
    private const RATE_MS    = 1300;

    private ?float $lastRequest = null;

    /**
     * Returns enriched artist metadata from MusicBrainz + TheAudioDB.
     * Result is cached for 7 days. Never throws — returns [] on any failure.
     * Returned array may contain: country, genre, bio, musicbrainz_id, photo_url.
     */
    public function enrichArtist(string $name): array
    {
        if (blank($name) || $name === 'Desconhecido') {
            return [];
        }

        $cacheKey = 'mb_artist_' . md5(mb_strtolower($name));
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $result = $this->fetchArtist($name) ?? [];
        $tadb   = $this->fetchTheAudioDB($name);

        // Photo only from TheAudioDB (MusicBrainz has no artist images)
        if (! empty($tadb['photo_url'])) {
            $result['photo_url'] = $tadb['photo_url'];
        }
        // Portuguese bio: TheAudioDB PT takes priority over Wikipedia
        if (! empty($tadb['bio'])) {
            $result['bio'] = $tadb['bio'];
        }
        // TheAudioDB overrides Wikipedia bios where it has data (music-focused bios)
        foreach (['bio_en', 'bio_es', 'bio_fr'] as $key) {
            if (! empty($tadb[$key])) {
                $result[$key] = $tadb[$key];
            }
        }
        // TheAudioDB genre as fallback when MusicBrainz returned nothing
        if (empty($result['genre']) && ! empty($tadb['genre'])) {
            $result['genre'] = $tadb['genre'];
        }

        if (! empty($result)) {
            Cache::put($cacheKey, $result, now()->addDays(7));
        }

        return $result;
    }

    /**
     * Downloads a remote artist photo and stores it on the public disk.
     * Returns the stored path (relative to public disk) or null on failure.
     */
    public function downloadArtistPhoto(string $photoUrl, string $slug): ?string
    {
        try {
            $response = $this->http()->timeout(20)->get($photoUrl);
            if (! $response->successful()) {
                return null;
            }

            $contentType = $response->header('Content-Type', '');
            $ext = match (true) {
                str_contains($contentType, 'png')  => 'png',
                str_contains($contentType, 'webp') => 'webp',
                default                            => 'jpg',
            };

            $path = "artists/{$slug}.{$ext}";
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable $e) {
            Log::warning("[ArtistPhoto] Download failed for '{$slug}': {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Returns enriched song metadata from MusicBrainz.
     * Result is cached for 7 days. Never throws — returns [] on any failure.
     */
    public function enrichSong(string $title, string $artistName): array
    {
        if (blank($title)) {
            return [];
        }

        $cacheKey = 'mb_recording_' . md5(mb_strtolower("{$artistName}:{$title}"));
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $result = $this->fetchRecording($title, $artistName) ?? [];
        if (! empty($result)) {
            Cache::put($cacheKey, $result, now()->addDays(7));
        }
        return $result;
    }

    // ─── Private: Artist ───────────────────────────────────────────────────────

    private function fetchArtist(string $name): ?array
    {
        try {
            $this->rateLimit();
            $res = $this->http()->get(self::MB_BASE . '/artist', [
                'query' => "artist:{$name}",
                'fmt'   => 'json',
                'limit' => 5,
            ]);

            if (! $res->successful()) {
                return null;
            }

            $artists = $res->json('artists', []);
            if (empty($artists)) {
                return null;
            }

            // Prefer exact name match (case-insensitive)
            $artist = collect($artists)->first(
                fn ($a) => mb_strtolower($a['name']) === mb_strtolower($name)
            ) ?? $artists[0];

            $mbid = $artist['id'];

            // Fetch full detail with tags, genres and URL relations (for Wikipedia)
            $this->rateLimit();
            $detail = $this->http()->get(self::MB_BASE . "/artist/{$mbid}", [
                'inc' => 'tags+genres+url-rels',
                'fmt' => 'json',
            ]);

            if ($detail->successful()) {
                $artist = $detail->json();
            }

            $result = $this->parseArtist($artist);

            // Bios in all supported languages via Wikipedia (best-effort)
            foreach ($this->fetchWikipediaBios($artist) as $key => $bio) {
                $result[$key] = $bio;
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning("[MusicMetadata] Artist lookup failed for '{$name}': {$e->getMessage()}");
            return null;
        }
    }

    private function parseArtist(array $data): array
    {
        // Country — try direct field first, then area ISO codes
        $country = $data['country']
            ?? $data['area']['iso-3166-1-codes'][0]
            ?? null;

        // Genre — prefer curated genres over free-text tags
        $genre = collect($data['genres'] ?? [])
            ->sortByDesc('count')
            ->pluck('name')
            ->first()
            ?? collect($data['tags'] ?? [])
                ->sortByDesc('count')
                ->reject(fn ($t) => in_array($t['name'], [
                    'brazilian', 'portuguese', 'singer-songwriter', 'male vocalist',
                ]))
                ->pluck('name')
                ->first();

        return [
            'country'        => $country,
            'genre'          => $genre,
            'musicbrainz_id' => $data['id'] ?? null,
        ];
    }

    /**
     * Fetches artist bios in PT, EN, ES and FR from Wikipedia.
     * Strategy:
     *   1. MusicBrainz 'wikipedia' URL relation  → Wikipedia langlinks API
     *   2. MusicBrainz 'wikidata' URL relation   → Wikidata sitelinks API (more common today)
     * Returns an array keyed by 'bio' (PT), 'bio_en', 'bio_es', 'bio_fr'.
     */
    private function fetchWikipediaBios(array $artistData): array
    {
        $relations = $artistData['relations'] ?? [];

        // Strategy 1: direct Wikipedia URL
        $wikiUrl = collect($relations)
            ->where('type', 'wikipedia')
            ->pluck('url.resource')
            ->first();

        if ($wikiUrl) {
            return $this->biosFromWikipediaUrl($wikiUrl);
        }

        // Strategy 2: Wikidata URL (increasingly common in MusicBrainz)
        $wikidataUrl = collect($relations)
            ->where('type', 'wikidata')
            ->pluck('url.resource')
            ->first();

        if ($wikidataUrl && preg_match('#/wiki/(Q\d+)#i', $wikidataUrl, $m)) {
            return $this->biosFromWikidata($m[1]);
        }

        Log::info('[Wikipedia] No Wikipedia or Wikidata relation found in MusicBrainz data');
        return [];
    }

    private function biosFromWikipediaUrl(string $wikiUrl): array
    {
        if (! preg_match('#https?://([a-z]+)\.wikipedia\.org/wiki/(.+)#i', $wikiUrl, $m)) {
            return [];
        }

        $sourceLang  = $m[1];
        $sourceTitle = str_replace(' ', '_', urldecode($m[2]));
        $bios        = [];

        try {
            $this->rateLimit();
            $res = $this->http()->timeout(8)
                ->get("https://{$sourceLang}.wikipedia.org/api/rest_v1/page/summary/" . urlencode($sourceTitle));

            if ($res->successful() && ($bio = $res->json('extract'))) {
                $bios[$sourceLang === 'pt' ? 'bio' : "bio_{$sourceLang}"] = $bio;
            }

            // Fetch titles in the other three languages via MediaWiki langlinks
            $targets = array_values(array_diff(['pt', 'en', 'es', 'fr'], [$sourceLang]));

            $this->rateLimit();
            $langRes = $this->http()->timeout(8)->get(
                "https://{$sourceLang}.wikipedia.org/w/api.php",
                ['action' => 'query', 'titles' => $sourceTitle,
                 'prop' => 'langlinks', 'lllimit' => '500', 'format' => 'json']
            );

            if ($langRes->successful()) {
                $page      = reset($langRes->json('query.pages', []));
                $langlinks = collect($page['langlinks'] ?? [])
                    ->keyBy('lang')
                    ->only($targets)
                    ->map(fn ($l) => str_replace(' ', '_', $l['*']));

                foreach ($targets as $lang) {
                    if (! $langlinks->has($lang)) {
                        continue;
                    }
                    $this->rateLimit();
                    $bioRes = $this->http()->timeout(8)
                        ->get("https://{$lang}.wikipedia.org/api/rest_v1/page/summary/" . urlencode($langlinks[$lang]));

                    if ($bioRes->successful() && ($bio = $bioRes->json('extract'))) {
                        $bios[$lang === 'pt' ? 'bio' : "bio_{$lang}"] = $bio;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[Wikipedia] biosFromWikipediaUrl failed: {$e->getMessage()}");
        }

        return $bios;
    }

    private function biosFromWikidata(string $wikidataId): array
    {
        $bios    = [];
        $langMap = ['ptwiki' => 'pt', 'enwiki' => 'en', 'eswiki' => 'es', 'frwiki' => 'fr'];

        try {
            $this->rateLimit();
            $res = $this->http()->timeout(8)->get(
                'https://www.wikidata.org/w/api.php',
                [
                    'action'     => 'wbgetentities',
                    'ids'        => $wikidataId,
                    'props'      => 'sitelinks',
                    'sitefilter' => implode('|', array_keys($langMap)),
                    'format'     => 'json',
                ]
            );

            if (! $res->successful()) {
                return [];
            }

            $sitelinks = $res->json("entities.{$wikidataId}.sitelinks", []);
            Log::info("[Wikidata] {$wikidataId} sitelinks: " . implode(', ', array_keys($sitelinks)));

            foreach ($langMap as $site => $lang) {
                if (! isset($sitelinks[$site]['title'])) {
                    continue;
                }
                $title = str_replace(' ', '_', $sitelinks[$site]['title']);
                $this->rateLimit();
                $bioRes = $this->http()->timeout(8)
                    ->get("https://{$lang}.wikipedia.org/api/rest_v1/page/summary/" . urlencode($title));

                if ($bioRes->successful() && ($bio = $bioRes->json('extract'))) {
                    $bios[$lang === 'pt' ? 'bio' : "bio_{$lang}"] = $bio;
                    Log::info("[Wikidata→Wikipedia] {$lang}: {$title} (" . mb_strlen($bio) . " chars)");
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[Wikidata] biosFromWikidata failed for {$wikidataId}: {$e->getMessage()}");
        }

        return $bios;
    }

    // ─── Private: Recording ────────────────────────────────────────────────────

    private function fetchRecording(string $title, string $artistName): ?array
    {
        try {
            $this->rateLimit();

            $escapedTitle  = str_replace(['"', '\\'], ['\\"', '\\\\'], $title);
            $escapedArtist = str_replace(['"', '\\'], ['\\"', '\\\\'], $artistName);
            $query         = "recording:\"{$escapedTitle}\" AND artist:\"{$escapedArtist}\"";

            $res = $this->http()->get(self::MB_BASE . '/recording', [
                'query' => $query,
                'fmt'   => 'json',
                'limit' => 5,
            ]);

            if (! $res->successful()) {
                return null;
            }

            $recordings = $res->json('recordings', []);
            if (empty($recordings)) {
                return null;
            }

            // Prefer exact title match
            $recording = collect($recordings)->first(
                fn ($r) => mb_strtolower($r['title']) === mb_strtolower($title)
            ) ?? $recordings[0];

            return $this->parseRecording($recording);
        } catch (\Throwable $e) {
            Log::warning("[MusicMetadata] Recording lookup failed for '{$title}': {$e->getMessage()}");
            return null;
        }
    }

    private function parseRecording(array $data): array
    {
        // Year from first-release-date; fallback to earliest dated release
        $year = null;
        if (! empty($data['first-release-date'])) {
            $y = (int) substr($data['first-release-date'], 0, 4);
            if ($y >= 1900 && $y <= (int) date('Y')) {
                $year = $y;
            }
        }
        if ($year === null) {
            $year = collect($data['releases'] ?? [])
                ->filter(fn ($r) => ! empty($r['date']))
                ->map(fn ($r) => (int) substr($r['date'], 0, 4))
                ->filter(fn ($y) => $y >= 1900 && $y <= (int) date('Y'))
                ->sort()
                ->first();
        }

        // Album: earliest dated release to avoid compilations being listed first
        $album = collect($data['releases'] ?? [])
            ->filter(fn ($r) => ! empty($r['title']))
            ->sortBy('date')
            ->pluck('title')
            ->first();

        // Artist credit: first credited artist name
        $artist = collect($data['artist-credit'] ?? [])
            ->pluck('artist.name')
            ->filter()
            ->first();

        return [
            'year'           => $year,
            'album'          => $album,
            'artist'         => $artist,
            'musicbrainz_id' => $data['id'] ?? null,
        ];
    }

    // ─── TheAudioDB ────────────────────────────────────────────────────────────

    private function fetchTheAudioDB(string $name): array
    {
        $cacheKey = 'tadb_artist_' . md5(mb_strtolower($name));
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $res = $this->http()->timeout(10)
                ->get('https://www.theaudiodb.com/api/v1/json/2/search.php', ['s' => $name]);

            if (! $res->successful()) {
                return [];
            }

            $artists = $res->json('artists');
            if (empty($artists)) {
                return [];
            }

            // Prefer exact name match
            $artist = collect($artists)->first(
                fn ($a) => mb_strtolower($a['strArtist'] ?? '') === mb_strtolower($name)
            ) ?? $artists[0];

            $result = [];

            if (! empty($artist['strArtistThumb'])) {
                $result['photo_url'] = $artist['strArtistThumb'];
            }

            // Portuguese bio as primary (better for the app's audience)
            if (! empty($artist['strBiographyPT'])) $result['bio']    = $artist['strBiographyPT'];
            if (! empty($artist['strBiographyEN'])) $result['bio_en'] = $artist['strBiographyEN'];
            if (! empty($artist['strBiographyES'])) $result['bio_es'] = $artist['strBiographyES'];
            if (! empty($artist['strBiographyFR'])) $result['bio_fr'] = $artist['strBiographyFR'];

            // Fallback for Portuguese: use English if PT is absent
            if (empty($result['bio']) && ! empty($result['bio_en'])) {
                $result['bio'] = $result['bio_en'];
            }

            if (! empty($artist['strGenre'])) {
                $result['genre'] = $artist['strGenre'];
            }

            if (! empty($result)) {
                Cache::put($cacheKey, $result, now()->addDays(7));
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning("[TheAudioDB] Lookup failed for '{$name}': {$e->getMessage()}");
            return [];
        }
    }

    // ─── Rate limiter ──────────────────────────────────────────────────────────

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::withHeaders(['User-Agent' => self::USER_AGENT])->timeout(10);
        $cacert = storage_path('app/cacert.pem');
        return file_exists($cacert) ? $client->withOptions(['verify' => $cacert]) : $client;
    }

    private function rateLimit(): void
    {
        if ($this->lastRequest !== null) {
            $elapsedMs = (microtime(true) - $this->lastRequest) * 1000;
            if ($elapsedMs < self::RATE_MS) {
                usleep((int) ((self::RATE_MS - $elapsedMs) * 1000));
            }
        }
        $this->lastRequest = microtime(true);
    }
}
