<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Models\Chord;
use App\Models\Import;
use App\Models\Song;
use App\Services\Import\ChordDictionary;
use App\Services\Import\FormatDetector;
use App\Services\Import\MusicMetadataService;
use App\Services\Import\ZipBatchImporter;
use App\Services\YouTubeSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ProcessBatchImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(
        private readonly int $importId,
        private readonly string $tempDir,
        private readonly ?int $defaultCategoryId,
        private readonly bool $overwriteDuplicates,
        private readonly bool $publishByDefault,
    ) {}

    public function handle(
        ZipBatchImporter $batchImporter,
        FormatDetector $detector,
        MusicMetadataService $metadata,
        YouTubeSearchService $youtubeSearch,
    ): void {
        $import = Import::findOrFail($this->importId);
        $import->update(['status' => 'processing']);

        $files = $batchImporter->listFiles($this->tempDir);
        $import->update(['total_files' => count($files)]);

        $log          = [];
        $importedCount = 0;
        $failedCount  = 0;

        foreach ($files as $filePath) {
            $filename = basename($filePath);

            try {
                $format = $detector->detect($filePath);
                $data   = $batchImporter->convertFile($filePath, $format);

                $enrichment = $this->persistSong($data, $format, $metadata, $youtubeSearch);

                $log[] = array_merge(['file' => $filename, 'status' => 'ok'], $enrichment);
                $importedCount++;
            } catch (\Throwable $e) {
                $log[] = ['file' => $filename, 'status' => 'error', 'message' => $e->getMessage()];
                $failedCount++;
            }
        }

        $import->update([
            'status'         => $failedCount === count($files) ? 'failed' : 'completed',
            'imported_count' => $importedCount,
            'failed_count'   => $failedCount,
            'log'            => $log,
        ]);

        $batchImporter->cleanup($this->tempDir);
    }

    private function persistSong(array $data, string $format, MusicMetadataService $metadata, YouTubeSearchService $youtubeSearch): array
    {
        $title      = $data['title']
            ?? $this->extractTitleFromContent($data['content'] ?? '')
            ?? $this->inferTitleFromFile($data);
        $artistName = $data['artist']
            ?? $this->extractArtistFromContent($data['content'] ?? '');

        // Split "Title - Artist" before enrichment so MusicBrainz gets the real title
        if ($artistName === null && str_contains($title, ' - ')) {
            [$titlePart, $artistPart] = array_map('trim', explode(' - ', $title, 2));
            $title      = $titlePart;
            $artistName = $artistPart;
        }

        // ── MusicBrainz + TheAudioDB enrichment ───────────────────────────────
        $songMeta = $metadata->enrichSong($title, $artistName ?? '');

        if ($artistName === null && ! empty($songMeta['artist'])) {
            $artistName = $songMeta['artist'];
        }
        $artistName = $artistName ?? 'Desconhecido';

        $artistMeta = $metadata->enrichArtist($artistName);

        $photoPath = null;
        if (! empty($artistMeta['photo_url'])) {
            $photoPath = $metadata->downloadArtistPhoto($artistMeta['photo_url'], artist_slug($artistName));
        }

        $artist = Artist::firstOrCreate(
            ['slug' => artist_slug($artistName)],
            [
                'name'           => $artistName,
                'country'        => $artistMeta['country'] ?? 'BR',
                'genre'          => $artistMeta['genre']   ?? null,
                'bio'            => $artistMeta['bio']     ?? null,
                'bio_en'         => $artistMeta['bio_en']  ?? null,
                'bio_es'         => $artistMeta['bio_es']  ?? null,
                'bio_fr'         => $artistMeta['bio_fr']  ?? null,
                'photo_path'     => $photoPath,
                'musicbrainz_id' => $artistMeta['musicbrainz_id'] ?? null,
            ]
        );

        if (! $artist->wasRecentlyCreated && ! empty($artistMeta)) {
            $updates = array_filter([
                'genre'          => $artist->genre          === null ? ($artistMeta['genre']  ?? null) : null,
                'bio'            => $artist->bio            === null ? ($artistMeta['bio']    ?? null) : null,
                'bio_en'         => $artist->bio_en         === null ? ($artistMeta['bio_en'] ?? null) : null,
                'bio_es'         => $artist->bio_es         === null ? ($artistMeta['bio_es'] ?? null) : null,
                'bio_fr'         => $artist->bio_fr         === null ? ($artistMeta['bio_fr'] ?? null) : null,
                'musicbrainz_id' => $artist->musicbrainz_id === null ? ($artistMeta['musicbrainz_id'] ?? null) : null,
                'photo_path'     => $artist->photo_path     === null ? $photoPath : null,
                'country'        => ($artist->country === 'BR' && isset($artistMeta['country']) && $artistMeta['country'] !== 'BR')
                    ? $artistMeta['country']
                    : null,
            ], fn ($v) => $v !== null);

            if ($updates) {
                $artist->update($updates);
            }
        }

        // ── Slug resolution — after artist is known ───────────────────────────
        // Slug is calculated from the final (possibly split) title.
        // Collision with a DIFFERENT artist generates "title-artistslug" instead
        // of being rejected as a duplicate.
        $baseSlug = Str::slug($title);
        $existing = Song::where('slug', $baseSlug)->first();
        $slug     = $baseSlug;

        if ($existing) {
            if ($existing->artist_id === $artist->id) {
                // True duplicate: same song, same artist
                if (! $this->overwriteDuplicates) {
                    throw new \RuntimeException("Duplicata ignorada: {$title}");
                }
            } else {
                // Same title, different artist — append artist slug to disambiguate
                $slug     = $this->uniqueSlug($baseSlug . '-' . $artist->slug);
                $existing = null;
            }
        }

        $youtubeId = $data['youtube_id'] ?? $youtubeSearch->searchVideoId($title, $artistName);

        $songData = [
            'artist_id'      => $artist->id,
            'category_id'    => $this->defaultCategoryId,
            'title'          => $title,
            'slug'           => $slug,
            'key'            => $data['key'] ?? $this->inferKeyFromChords($data['content'] ?? ''),
            'year'           => $songMeta['year']  ?? ($data['year']  ?? null),
            'album'          => $songMeta['album'] ?? ($data['album'] ?? null),
            'musicbrainz_id' => $songMeta['musicbrainz_id'] ?? null,
            'youtube_id'     => $youtubeId,
            'is_published'   => $this->publishByDefault,
        ];

        if ($existing && $this->overwriteDuplicates) {
            $existing->update($songData);
            $song = $existing;
        } else {
            $song = Song::create($songData);
        }

        Chord::updateOrCreate(
            ['song_id' => $song->id, 'is_default' => true],
            [
                'content'       => $data['content'],
                'version_label' => 'Padrão',
                'source'        => $format,
                'tab_content'   => $data['tab_content'] ?? null,
                'is_default'    => true,
            ]
        );

        // Populate chord_diagrams for every chord referenced in this song
        // that doesn't already have an entry, using the built-in dictionary.
        preg_match_all('/\[([A-G][#b]?[^\]]*)\]/', $data['content'], $chordMatches);
        if (! empty($chordMatches[1])) {
            ChordDictionary::seedMissing(array_unique($chordMatches[1]));
        }

        return [
            'from_file' => array_filter([
                'Título'    => $data['title']      ?? null,
                'Artista'   => $data['artist']     ?? null,
                'Tom'       => $data['key']        ?? null,
                'Ano'       => $data['year']       ?? null,
                'Álbum'     => $data['album']      ?? null,
                'YouTube'   => $youtubeId,
            ]),
            'from_api' => array_filter([
                'Ano'           => $songMeta['year']            ?? null,
                'Álbum'         => $songMeta['album']           ?? null,
                'País (artista)'=> $artistMeta['country']       ?? null,
                'Gênero'        => $artistMeta['genre']         ?? null,
                'Foto'          => $photoPath                   ?? null,
                'MBID música'   => $songMeta['musicbrainz_id']  ?? null,
                'MBID artista'  => $artistMeta['musicbrainz_id']?? null,
            ]),
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $slug   = $base;
        $suffix = 2;
        while (Song::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }
        return $slug;
    }

    private function extractTitleFromContent(string $content): ?string
    {
        if (preg_match('/\{(?:title|t|song|name):\s*([^}]+)\}/iu', $content, $m)) {
            $v = trim($m[1]);
            return $v !== '' ? $v : null;
        }
        return null;
    }

    private function extractArtistFromContent(string $content): ?string
    {
        if (preg_match('/\{(?:artist|composer|lyricist|subtitle|st|by):\s*([^}]+)\}/iu', $content, $m)) {
            $v = trim($m[1]);
            return $v !== '' ? $v : null;
        }
        return null;
    }

    /**
     * Derive a human-readable title from the file path when ChordPro metadata
     * is absent. Examples:
     *   "africa.pro"          → "Africa"
     *   "toto_africa.cho"     → "Toto Africa"
     *   "Africa - Toto.txt"   → "Africa - Toto" (split handled by caller)
     */
    private function inferTitleFromFile(array $data): string
    {
        if (! empty($data['_filename'])) {
            $name = pathinfo($data['_filename'], PATHINFO_FILENAME);
            $name = str_replace(['_', '-'], ' ', $name);
            return trim(ucwords(strtolower($name)));
        }
        return 'Sem título';
    }

    /**
     * Infer the key of the song from the chords used.
     * Uses the most frequent chord root as the tonic, and determines major/minor
     * by counting major vs minor chords.
     */
    private function inferKeyFromChords(string $content): ?string
    {
        if (empty($content)) {
            return null;
        }

        // Extract all [Chord] markers
        preg_match_all('/\[([A-G][#b]?[^\]]*)\]/', $content, $matches);
        if (empty($matches[1])) {
            return null;
        }

        $chordRoots = [];
        $minorChordCount = 0;
        $majorChordCount = 0;

        foreach ($matches[1] as $chord) {
            if (preg_match('/^([A-G][#b]?)/', $chord, $m)) {
                $chordRoots[] = $m[1];

                // Count minor vs major chords (simple heuristic)
                if (preg_match('/m(?!aj|M)/', $chord)) {
                    $minorChordCount++;
                } else {
                    $majorChordCount++;
                }
            }
        }

        if (empty($chordRoots)) {
            return null;
        }

        // Most frequent root is the tonic
        $rootCounts = array_count_values($chordRoots);
        arsort($rootCounts);
        $tonic = array_key_first($rootCounts);

        // Determine mode: if more minor chords, use minor
        $mode = ($minorChordCount > $majorChordCount) ? 'm' : '';

        return $tonic . $mode;
    }
}
