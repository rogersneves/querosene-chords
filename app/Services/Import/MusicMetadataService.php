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
     * Returns enriched artist metadata from MusicBrainz.
     * Result is cached for 7 days. Never throws — returns [] on any failure.
     */
    public function enrichArtist(string $name): array
    {
        if (blank($name) || $name === 'Desconhecido') {
            return [];
        }

        return Cache::remember(
            'mb_artist_' . md5(mb_strtolower($name)),
            now()->addDays(7),
            fn () => $this->fetchArtist($name) ?? []
        );
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

        return Cache::remember(
            'mb_recording_' . md5(mb_strtolower("{$artistName}:{$title}")),
            now()->addDays(7),
            fn () => $this->fetchRecording($title, $artistName) ?? []
        );
    }

    // ─── Private: Artist ───────────────────────────────────────────────────────

    private function fetchArtist(string $name): ?array
    {
        try {
            $this->rateLimit();
            $res = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get(self::MB_BASE . '/artist', [
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
            $detail = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get(self::MB_BASE . "/artist/{$mbid}", [
                    'inc' => 'tags+genres+url-rels',
                    'fmt' => 'json',
                ]);

            if ($detail->successful()) {
                $artist = $detail->json();
            }

            $result = $this->parseArtist($artist);

            // Bio via Wikipedia (best-effort; failure is silently ignored)
            $bio = $this->fetchWikipediaBio($artist);
            if ($bio) {
                $result['bio'] = $bio;
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

    private function fetchWikipediaBio(array $artistData): ?string
    {
        $relations = $artistData['relations'] ?? [];

        $wikiUrl = collect($relations)
            ->where('type', 'wikipedia')
            ->pluck('url.resource')
            ->first();

        if (! $wikiUrl) {
            return null;
        }

        // Extract language + article title from URL
        // e.g. https://pt.wikipedia.org/wiki/Legi%C3%A3o_Urbana
        if (! preg_match('#https?://([a-z]+)\.wikipedia\.org/wiki/(.+)#i', $wikiUrl, $m)) {
            return null;
        }

        $lang  = $m[1];
        $title = $m[2];
        $title = urldecode($title);

        try {
            $this->rateLimit();
            $res = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(8)
                ->get("https://{$lang}.wikipedia.org/api/rest_v1/page/summary/" . urlencode($title));

            if ($res->successful()) {
                return $res->json('extract');
            }
        } catch (\Throwable) {
            // Bio is a nice-to-have; swallow silently
        }

        return null;
    }

    // ─── Private: Recording ────────────────────────────────────────────────────

    private function fetchRecording(string $title, string $artistName): ?array
    {
        try {
            $this->rateLimit();

            $escapedTitle  = str_replace(['"', '\\'], ['\\"', '\\\\'], $title);
            $escapedArtist = str_replace(['"', '\\'], ['\\"', '\\\\'], $artistName);
            $query         = "recording:\"{$escapedTitle}\" AND artist:\"{$escapedArtist}\"";

            $res = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get(self::MB_BASE . '/recording', [
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
        // Year from first-release-date (YYYY or YYYY-MM-DD)
        $year = null;
        if (! empty($data['first-release-date'])) {
            $y = (int) substr($data['first-release-date'], 0, 4);
            if ($y >= 1900 && $y <= (int) date('Y')) {
                $year = $y;
            }
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

    // ─── Rate limiter ──────────────────────────────────────────────────────────

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
