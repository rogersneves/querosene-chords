<?php

namespace App\Services\Import;

class FormatDetector
{
    public function detect(string $filePath): string
    {
        $bytes = file_get_contents($filePath, false, null, 0, 512);

        if ($bytes === false) {
            return 'unknown';
        }

        // GuitarPro magic bytes (GP4/5)
        if (str_starts_with($bytes, "\x30\x35") || str_starts_with($bytes, "FICHIER GUITAR PRO")) {
            return 'guitarpro';
        }

        // GuitarPro 7+ (GPX — ZIP-based XML)
        if (str_starts_with($bytes, "PK") && str_contains($bytes, 'score.gpif')) {
            return 'guitarpro';
        }

        // MusicXML
        if (str_contains($bytes, '<?xml') &&
            (str_contains($bytes, '<score-partwise') || str_contains($bytes, '<score-timewise'))) {
            return 'musicxml';
        }

        // ChordPro — long form {title:} or short form {t:} / {st:}
        if (preg_match('/^\{(?:title|t|artist|st|subtitle|by|key|k|capo|tempo|bpm|composer):/mi', $bytes)) {
            return 'chordpro';
        }

        // ChordPro without meta directives — file contains [Chord] inline markers
        // but does NOT look like a Cifra Club alternating-line file
        if (preg_match('/^\[[A-G][#b]?[^\]]*\]/m', $bytes) && ! $this->looksLikeCifraClub($bytes)) {
            return 'chordpro';
        }

        // ZIP
        if (str_starts_with($bytes, "PK\x03\x04")) {
            return 'zip';
        }

        // Cifra Club TXT — detect alternating chord+lyric line pattern
        if ($this->looksLikeCifraClub($bytes)) {
            return 'cifraclub';
        }

        return 'unknown';
    }

    private function looksLikeCifraClub(string $content): bool
    {
        $lines = explode("\n", $content);
        $chordLineCount = 0;

        foreach ($lines as $line) {
            if ($this->isChordLine($line)) {
                $chordLineCount++;
                if ($chordLineCount >= 2) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isChordLine(string $line): bool
    {
        $trimmed = trim($line);

        if (empty($trimmed)) {
            return false;
        }

        // Tab lines (E|, B|, G|, D|, A|, e|)
        if (preg_match('/^[EBGDAe]\|/', $trimmed)) {
            return false;
        }

        // One chord token (no spaces) is enough for a chord line — handles "E", "Am", "F°", etc.
        // Two-or-more pattern also accepts extended chords like "C#7(13-)/G#" and "F°"
        $cp = '[A-G][#b]?(?:°|m(?:aj)?|M(?:aj)?|dim|aug|sus[24]?|add[0-9]*)?[0-9]*M?(?:\([^)]+\))?(?:\/[A-G][#b]?)?';
        return (bool) preg_match('/^(\s*' . $cp . '\s+)+' . $cp . '\s*$/u', $trimmed)
            || (bool) preg_match('/^\s*' . $cp . '\s*$/u', $trimmed);
    }
}
