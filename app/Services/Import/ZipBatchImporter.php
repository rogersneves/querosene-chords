<?php

namespace App\Services\Import;

use Illuminate\Support\Str;

class ZipBatchImporter
{
    private FormatDetector $detector;
    private CifraClubConverter $cifraClub;
    private ChordProImporter $chordPro;
    private GuitarProConverter $guitarPro;
    private MusicXmlConverter $musicXml;

    public function __construct(
        FormatDetector $detector,
        CifraClubConverter $cifraClub,
        ChordProImporter $chordPro,
        GuitarProConverter $guitarPro,
        MusicXmlConverter $musicXml,
    ) {
        $this->detector = $detector;
        $this->cifraClub = $cifraClub;
        $this->chordPro = $chordPro;
        $this->guitarPro = $guitarPro;
        $this->musicXml = $musicXml;
    }

    public function extract(string $zipPath): string
    {
        $uuid = Str::uuid();
        $tempDir = storage_path("app/temp/imports/{$uuid}");

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Não foi possível abrir o arquivo ZIP.');
        }

        $zip->extractTo($tempDir);
        $zip->close();

        return $tempDir;
    }

    public function listFiles(string $tempDir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            $basename = $file->getFilename();

            // Skip Mac OS metadata and hidden files
            if (str_contains($path, '__MACOSX') || str_starts_with($basename, '.')) {
                continue;
            }

            $files[] = $path;
        }

        return $files;
    }

    public function preview(string $tempDir, int $limit = 5): array
    {
        $files = $this->listFiles($tempDir);
        $results = [];

        foreach (array_slice($files, 0, $limit) as $filePath) {
            try {
                $format = $this->detector->detect($filePath);
                $data = $this->convertFile($filePath, $format);

                $results[] = [
                    'filename' => basename($filePath),
                    'format' => $format,
                    'title' => $data['title'] ?? '(sem título)',
                    'artist' => $data['artist'] ?? '(desconhecido)',
                    'preview_lines' => implode("\n", array_slice(explode("\n", $data['content']), 0, 20)),
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'filename' => basename($filePath),
                    'format' => 'unknown',
                    'title' => null,
                    'artist' => null,
                    'preview_lines' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'total' => count($files),
            'preview' => $results,
        ];
    }

    public function convertFile(string $filePath, string $format): array
    {
        $data = match ($format) {
            'cifraclub' => $this->cifraClub->convert(file_get_contents($filePath)),
            'chordpro'  => $this->chordPro->convert(file_get_contents($filePath)),
            'guitarpro' => $this->guitarPro->convert($filePath),
            'musicxml'  => $this->musicXml->convert($filePath),
            default     => throw new \RuntimeException("Formato '{$format}' não suportado."),
        };

        // Always carry the original filename so the job can derive a title
        // when the file has no {title:} directive
        $data['_filename'] = basename($filePath);

        return $data;
    }

    public function cleanup(string $tempDir): void
    {
        if (is_dir($tempDir)) {
            $this->deleteDirectory($tempDir);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
