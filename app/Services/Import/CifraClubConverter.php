<?php

namespace App\Services\Import;

use App\Models\ChordDiagram;

class CifraClubConverter
{
    private FormatDetector $detector;

    private array $sectionMap = [
        'intro' => 'intro',
        'introduction' => 'intro',
        'verso' => 'verse',
        'verse' => 'verse',
        'primeira parte' => 'verse',
        'segunda parte' => 'verse',
        'terceira parte' => 'verse',
        'pre-refrão' => 'pre-chorus',
        'pre-chorus' => 'pre-chorus',
        'refrão' => 'chorus',
        'chorus' => 'chorus',
        'ponte' => 'bridge',
        'bridge' => 'bridge',
        'solo' => 'tab',
        'outro' => 'outro',
        'final' => 'outro',
        'interlúdio' => 'bridge',
    ];

    public function __construct(FormatDetector $detector)
    {
        $this->detector = $detector;
    }

    public function convert(string $content): array
    {
        $content = $this->normalizeLineEndings($content);
        $lines = explode("\n", $content);

        $title = '';
        $artist = '';
        $key = '';
        $chordProLines = [];
        $diagrams = [];
        $tabContent = [];
        $inTab = false;
        $currentSection = null;

        // Extract title from first non-empty line
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                if (str_contains($trimmed, ' - ')) {
                    [$artist, $title] = explode(' - ', $trimmed, 2);
                    $artist = trim($artist);
                    $title = trim($title);
                } else {
                    $title = $trimmed;
                }
                break;
            }
        }

        $i = 0;
        $total = count($lines);

        while ($i < $total) {
            $line = $lines[$i];
            $trimmed = trim($line);

            // Separator lines: "------", "======", "--- Acordes ---"
            if (preg_match('/^[-=_]{5,}$/', $trimmed) || preg_match('/^[-\s]*Acordes[-\s]*$/i', $trimmed)) {
                $i++;
                continue;
            }

            // Tab line: E|--- B|--- G|--- D|--- A|--- e|---
            if (preg_match('/^[EBGDAe]\|/', $trimmed)) {
                // Auto-open a tab section when tab lines appear outside one
                if ($currentSection !== 'tab') {
                    if ($currentSection !== null) {
                        $chordProLines[] = "{end_of_{$currentSection}}";
                    }
                    $currentSection = 'tab';
                    $chordProLines[] = '{start_of_tab}';
                }
                $inTab = true;
                $chordProLines[] = $trimmed;
                $tabContent[] = $line;
                $i++;
                continue;
            }

            // Within a tab block: skip blank lines and standalone fret numbers
            if ($inTab) {
                if (empty($trimmed) || preg_match('/^\d+$/', $trimmed)) {
                    $i++;
                    continue;
                }
                $inTab = false;
            }

            // Chord dictionary at footer (Am7 = X 0 2 0 1 0)
            if (preg_match('/^([A-G][#b]?(?:m|M|maj|min|dim|aug|sus|add)?[0-9]*)\s*=\s*([XxNn0-9\s]+)$/', $trimmed, $m)) {
                $this->parseDiagram($m[1], $m[2], $diagrams);
                $i++;
                continue;
            }

            // Section markers [Intro], [Verso], [Tab - Solo], etc.
            if (preg_match('/^\[(.+)\]$/', $trimmed, $m)) {
                $sectionKey = mb_strtolower($m[1]);
                $mapped = $this->sectionMap[$sectionKey] ?? $this->inferSectionType($sectionKey);

                if ($currentSection !== null) {
                    $chordProLines[] = "{end_of_{$currentSection}}";
                }
                $currentSection = $mapped;
                $chordProLines[] = "{start_of_{$mapped}: {$m[1]}}";
                $i++;
                continue;
            }

            // Sub-section part labels: "Parte 1 de 2", "Parte 2 de 2"
            if (preg_match('/^Parte\s+\d+\s+de\s+\d+$/iu', $trimmed)) {
                $chordProLines[] = "{c: {$trimmed}}";
                $i++;
                continue;
            }

            // Chord line followed by lyric line
            if ($this->detector->isChordLine($line) && isset($lines[$i + 1])) {
                $nextLine = $lines[$i + 1];

                if (!$this->detector->isChordLine($nextLine) && !preg_match('/^[EBGDAe]\|/', trim($nextLine))) {
                    $merged = $this->mergeChordAndLyric($line, $nextLine);
                    $chordProLines[] = $merged;
                    $i += 2;
                    continue;
                }

                // Chord line with no lyric below — emit as standalone
                $chords = $this->extractChordsInline($line, '');
                $chordProLines[] = $chords;
                $i++;
                continue;
            }

            // Regular lyric line
            if (!empty($trimmed) && $trimmed !== $title) {
                $chordProLines[] = $trimmed;
            }

            $i++;
        }

        if ($currentSection !== null) {
            $chordProLines[] = "{end_of_{$currentSection}}";
        }

        // Save diagrams to database
        foreach ($diagrams as $name => $data) {
            ChordDiagram::updateOrCreate(
                ['name' => $name],
                $data
            );
        }

        $header = $this->buildHeader($title, $artist, $key);
        $body = implode("\n", array_filter($chordProLines, fn($l) => $l !== ''));
        $chordPro = $header . "\n\n" . $body;

        return [
            'title' => $title,
            'artist' => $artist,
            'key' => $key ?: null,
            'content' => $chordPro,
            'tab_content' => !empty($tabContent) ? implode("\n", $tabContent) : null,
        ];
    }

    private function mergeChordAndLyric(string $chordLine, string $lyricLine): string
    {
        preg_match_all('/([A-G][#b]?(?:°|m(?:aj)?|M(?:aj)?|dim|aug|sus[24]?|add[0-9]*)?[0-9]*M?(?:\([^)]+\))?(?:\/[A-G][#b]?)?)/', $chordLine, $matches, PREG_OFFSET_CAPTURE);

        $lyric = rtrim($lyricLine);
        $offset = 0;

        foreach ($matches[1] as [$chord, $pos]) {
            // Adjust position for previously inserted chord tags
            $insert = "[{$chord}]";
            $lyricPos = $this->mapPosition($pos, $chordLine, $lyricLine);
            $insertAt = $lyricPos + $offset;

            if ($insertAt > mb_strlen($lyric)) {
                $lyric = str_pad($lyric, $insertAt) . $insert;
            } else {
                $lyric = mb_substr($lyric, 0, $insertAt) . $insert . mb_substr($lyric, $insertAt);
            }

            $offset += mb_strlen($insert);
        }

        return $lyric;
    }

    private function mapPosition(int $chordPos, string $chordLine, string $lyricLine): int
    {
        // Map position proportionally; chord and lyric lines may differ in length
        $lyricLen = mb_strlen(rtrim($lyricLine));
        if ($lyricLen === 0) {
            return $chordPos;
        }
        return min($chordPos, $lyricLen);
    }

    private function extractChordsInline(string $chordLine, string $lyric): string
    {
        preg_match_all('/([A-G][#b]?(?:°|m(?:aj)?|M(?:aj)?|dim|aug|sus[24]?|add[0-9]*)?[0-9]*M?(?:\([^)]+\))?(?:\/[A-G][#b]?)?)/', $chordLine, $matches);
        return implode(' ', array_map(fn($c) => "[{$c}]", $matches[1]));
    }

    private function parseDiagram(string $name, string $pattern, array &$diagrams): void
    {
        $strings = preg_split('/\s+/', trim($pattern));
        $stringsPattern = implode('', array_map(fn($s) => strtoupper($s), $strings));

        $fingering = [];
        foreach ($strings as $idx => $fret) {
            $fingering[$idx] = strtoupper($fret) === 'X' ? -1 : (int) $fret;
        }

        $diagrams[$name] = [
            'strings_pattern' => $stringsPattern,
            'fingering' => $fingering,
            'fingers' => null,
            'barre' => null,
        ];
    }

    private function inferSectionType(string $key): string
    {
        if (str_contains($key, 'tab') || str_contains($key, 'solo')) {
            return 'tab';
        }
        if (str_contains($key, 'refr') || str_contains($key, 'chorus')) {
            return 'chorus';
        }
        if (str_contains($key, 'ponte') || str_contains($key, 'bridge')) {
            return 'bridge';
        }
        return 'verse';
    }

    private function buildHeader(string $title, string $artist, string $key): string
    {
        $lines = [];

        if (!empty($title)) {
            $lines[] = "{title: {$title}}";
        }
        if (!empty($artist)) {
            $lines[] = "{artist: {$artist}}";
        }
        if (!empty($key)) {
            $lines[] = "{key: {$key}}";
        }

        return implode("\n", $lines);
    }

    private function normalizeLineEndings(string $content): string
    {
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        return str_replace(["\r\n", "\r"], "\n", $content);
    }
}
