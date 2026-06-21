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

                $enrichment = $this->persistSong($data, $format, $metadata);

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

    private function persistSong(array $data, string $format, MusicMetadataService $metadata): array
    {
        // Prefer metadata from file; try to extract from content as fallback before filename
        $title      = $data['title']
            ?? $this->extractTitleFromContent($data['content'] ?? '')
            ?? $this->inferTitleFromFile($data);
        $artistName = $data['artist']
            ?? $this->extractArtistFromContent($data['content'] ?? '');

        $slug     = Str::slug($title);
        $existing = Song::where('slug', $slug)->first();

        if ($existing && ! $this->overwriteDuplicates) {
            throw new \RuntimeException("Duplicata ignorada: {$title}");
        }

        // ── Enrich via MusicBrainz (only when we have a real title) ────────────
        // If title has artist embedded ("Africa - Toto"), try to split
        if ($artistName === null && str_contains($title, ' - ')) {
            [$titlePart, $artistPart] = array_map('trim', explode(' - ', $title, 2));
            $title      = $titlePart;
            $artistName = $artistPart;
        }

        // Lookup song first (with or without artist) — result may fill missing artist
        $songMeta = $metadata->enrichSong($title, $artistName ?? '');

        // If artist still unknown, use whatever MusicBrainz returned for the recording
        if ($artistName === null && ! empty($songMeta['artist'])) {
            $artistName = $songMeta['artist'];
        }

        $artistName = $artistName ?? 'Desconhecido';

        $artistMeta = $metadata->enrichArtist($artistName);

        $artist = Artist::firstOrCreate(
            ['slug' => Str::slug($artistName)],
            [
                'name'           => $artistName,
                'country'        => $artistMeta['country'] ?? 'BR',
                'genre'          => $artistMeta['genre']   ?? null,
                'bio'            => $artistMeta['bio']     ?? null,
                'musicbrainz_id' => $artistMeta['musicbrainz_id'] ?? null,
            ]
        );

        // If the artist already existed, silently fill any missing fields
        if (! $artist->wasRecentlyCreated && ! empty($artistMeta)) {
            $updates = array_filter([
                'genre'          => $artist->genre          === null ? ($artistMeta['genre']  ?? null) : null,
                'bio'            => $artist->bio            === null ? ($artistMeta['bio']    ?? null) : null,
                'musicbrainz_id' => $artist->musicbrainz_id === null ? ($artistMeta['musicbrainz_id'] ?? null) : null,
                // Only overwrite country if we still have the default 'BR' and MB returned something different
                'country'        => ($artist->country === 'BR' && isset($artistMeta['country']) && $artistMeta['country'] !== 'BR')
                    ? $artistMeta['country']
                    : null,
            ], fn ($v) => $v !== null);

            if ($updates) {
                $artist->update($updates);
            }
        }

        $songData = [
            'artist_id'      => $artist->id,
            'category_id'    => $this->defaultCategoryId,
            'title'          => $title,
            'slug'           => $slug,
            'key'            => $data['key'] ?? null,
            'year'           => $songMeta['year']  ?? ($data['year']  ?? null),
            'album'          => $songMeta['album'] ?? ($data['album'] ?? null),
            'musicbrainz_id' => $songMeta['musicbrainz_id'] ?? null,
            'youtube_id'     => $data['youtube_id'] ?? null,
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
                'YouTube'   => $data['youtube_id'] ?? null,
            ]),
            'from_api' => array_filter([
                'Ano'           => $songMeta['year']            ?? null,
                'Álbum'         => $songMeta['album']           ?? null,
                'País (artista)'=> $artistMeta['country']       ?? null,
                'Gênero'        => $artistMeta['genre']         ?? null,
                'MBID música'   => $songMeta['musicbrainz_id']  ?? null,
                'MBID artista'  => $artistMeta['musicbrainz_id']?? null,
            ]),
        ];
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

    public function failed(\Throwable $exception): void
    {
        Import::where('id', $this->importId)->update([
            'status' => 'failed',
            'log'    => [['status' => 'error', 'message' => $exception->getMessage()]],
        ]);
    }
}
