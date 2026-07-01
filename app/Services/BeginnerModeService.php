<?php

namespace App\Services;

class BeginnerModeService
{
    private const CHROMATIC = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    private const FLAT_MAP  = ['Db' => 'C#', 'Eb' => 'D#', 'Fb' => 'E', 'Gb' => 'F#', 'Ab' => 'G#', 'Bb' => 'A#', 'Cb' => 'B'];

    // Open-position chords that don't need barre despite non-standard roots
    private const OPEN_EXCEPTIONS = ['B7', 'Fmaj7', 'F7M', 'D/F#', 'G/B', 'C/G', 'Am/E', 'G/F#', 'Em/B'];

    /**
     * Finds the best capo position (1–7) that minimizes barre chords.
     * Returns null if the song already needs no barres, or if no capo helps.
     *
     * @param  array  $chordList  Unique chord names from Song::$chord_list
     * @return array{capo:int,semitones:int,barreCount:int,originalBarreCount:int}|null
     */
    public function analyze(array $chordList): ?array
    {
        if (empty($chordList)) {
            return null;
        }

        $originalBarres = $this->countBarres($chordList, 0);

        if ($originalBarres === 0) {
            return null;
        }

        $best = ['capo' => 0, 'barreCount' => $originalBarres];

        for ($capo = 1; $capo <= 7; $capo++) {
            $count = $this->countBarres($chordList, -$capo);

            if ($count < $best['barreCount']) {
                $best = ['capo' => $capo, 'barreCount' => $count];

                if ($count === 0) {
                    break;
                }
            }
        }

        if ($best['capo'] === 0) {
            return null;
        }

        return [
            'capo'               => $best['capo'],
            'semitones'          => -$best['capo'],
            'barreCount'         => $best['barreCount'],
            'originalBarreCount' => $originalBarres,
        ];
    }

    private function countBarres(array $chords, int $semitones): int
    {
        $count = 0;

        foreach ($chords as $chord) {
            $transposed = $this->transposeChord((string) $chord, $semitones);
            if (!$this->isOpen($transposed)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns true if the chord can be played without a barre in standard tuning.
     *
     * Open major/dom/maj7/sus shapes: A, C, D, E, G roots
     * Open minor shapes:              Am, Dm, Em only
     * Special open positions:         B7, Fmaj7, D/F#, G/B, etc.
     */
    private function isOpen(string $chord): bool
    {
        if (in_array($chord, self::OPEN_EXCEPTIONS, true)) {
            return true;
        }

        // For slash chords evaluate only the main chord
        $main = str_contains($chord, '/') ? explode('/', $chord, 2)[0] : $chord;

        if (!preg_match('/^([A-G][#b]?)(.*)$/', $main, $m)) {
            return true; // unrecognizable format — assume open
        }

        $root    = self::FLAT_MAP[$m[1]] ?? $m[1];
        $quality = $m[2]; // '', 'm', 'm7', 'maj7', '7', 'sus2', 'add9' …

        // Minor family: starts with 'm' but NOT 'maj' (to distinguish Cmaj7 from Cm)
        if (preg_match('/^m(?!aj)/i', $quality)) {
            return in_array($root, ['A', 'D', 'E'], true);
        }

        // Major / dominant7 / major7 / sus / add / power chords
        return in_array($root, ['A', 'C', 'D', 'E', 'G'], true);
    }

    /** Transposes every [Chord] token inside a ChordPro string. */
    public function transposeContent(string $content, int $semitones): string
    {
        if ($semitones === 0) return $content;

        return preg_replace_callback(
            '/\[([A-G][#b]?[^\]]*)\]/',
            fn($m) => '[' . $this->transposeChord($m[1], $semitones) . ']',
            $content
        ) ?? $content;
    }

    /** Transposes a single chord or key name (e.g. "Am", "C#"). */
    public function transposeKey(string $key, int $semitones): string
    {
        if ($semitones === 0) return $key;
        return $this->transposeChord($key, $semitones) ?: $key;
    }

    private function transposeChord(string $chord, int $semitones): string
    {
        if ($semitones === 0) {
            return $chord;
        }

        if (str_contains($chord, '/')) {
            [$main, $bass] = explode('/', $chord, 2);
            $pb = $this->parseRoot(trim($bass));

            return $this->transposeChord($main, $semitones) . '/'
                . ($pb ? $this->transposeNote($pb['root'], $semitones) . $pb['rest'] : $bass);
        }

        $p = $this->parseRoot($chord);

        return $p
            ? $this->transposeNote($p['root'], $semitones) . $p['rest']
            : $chord;
    }

    private function transposeNote(string $note, int $semitones): string
    {
        $note = self::FLAT_MAP[$note] ?? $note;
        $idx  = array_search($note, self::CHROMATIC, true);

        return $idx !== false
            ? self::CHROMATIC[(($idx + $semitones) % 12 + 12) % 12]
            : $note;
    }

    private function parseRoot(string $chord): ?array
    {
        return preg_match('/^([A-G][#b]?)(.*)$/', $chord, $m)
            ? ['root' => $m[1], 'rest' => $m[2]]
            : null;
    }
}
