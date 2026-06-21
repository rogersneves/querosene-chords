<?php

namespace App\Services\Import;

class ChordProImporter
{
    /**
     * ChordPro directive aliases (long and short forms).
     * Keys are canonical names used internally; values are the regex alternation
     * of all directive names that map to that field.
     */
    private const ALIASES = [
        'title'  => 'title|t|song|name',
        'artist' => 'artist|composer|lyricist|subtitle|st|by',
        'key'    => 'key|k',
        'capo'   => 'capo|c',
        'tempo'  => 'tempo|bpm',
    ];

    public function convert(string $content): array
    {
        $content = $this->normalize($content);

        if (! $this->isValid($content)) {
            throw new \InvalidArgumentException('O arquivo não parece ser um ChordPro válido.');
        }

        return [
            'title'       => $this->extractMeta($content, 'title'),
            'artist'      => $this->extractMeta($content, 'artist'),
            'key'         => $this->extractMeta($content, 'key'),
            'content'     => $content,
            'tab_content' => null,
        ];
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function normalize(string $content): string
    {
        // Strip UTF-8 BOM (EF BB BF) if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Normalize encoding to UTF-8
        $detected = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($detected && $detected !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $detected);
        }

        // Normalize line endings
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    private function isValid(string $content): bool
    {
        // Has at least one recognized directive OR inline chord bracket
        return preg_match('/\{(?:' . implode('|', array_values(self::ALIASES)) . '):/i', $content) === 1
            || preg_match('/\[[A-G][#b]?/i', $content) === 1;
    }

    private function extractMeta(string $content, string $field): ?string
    {
        $aliases = self::ALIASES[$field] ?? $field;

        // Match {directive: value} — value may contain anything except }
        if (preg_match('/\{(?:' . $aliases . '):\s*([^}]+)\}/i', $content, $m)) {
            $value = trim($m[1]);
            return $value !== '' ? $value : null;
        }

        return null;
    }
}
