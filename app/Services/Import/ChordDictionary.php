<?php

namespace App\Services\Import;

use App\Models\ChordDiagram;

/**
 * Built-in guitar chord dictionary.
 *
 * strings_pattern: 6 characters, one per string from low-E to high-e.
 *   x = muted  |  0 = open  |  1–9 = fret number
 *
 * barre: fret at which the full barre is pressed (null = no barre).
 */
class ChordDictionary
{
    /**
     * Returns the full dictionary as an associative array:
     *   'chord_name' => ['pattern' => '133211', 'barre' => 1]
     */
    public static function all(): array
    {
        return [
            // ── C family ────────────────────────────────────────────────────
            'C'      => ['pattern' => 'x32010', 'barre' => null],
            'Cm'     => ['pattern' => 'x35543', 'barre' => 3],
            'C7'     => ['pattern' => 'x32310', 'barre' => null],
            'Cmaj7'  => ['pattern' => 'x32000', 'barre' => null],
            'C7M'    => ['pattern' => 'x32000', 'barre' => null],
            'Cm7'    => ['pattern' => 'x35343', 'barre' => 3],
            'Cadd9'  => ['pattern' => 'x32030', 'barre' => null],
            'Csus2'  => ['pattern' => 'x30030', 'barre' => null],
            'Csus4'  => ['pattern' => 'x33010', 'barre' => null],
            'C#'     => ['pattern' => 'x46664', 'barre' => 4],
            'C#m'    => ['pattern' => 'x46654', 'barre' => 4],
            'C#7'    => ['pattern' => 'x46466', 'barre' => 4],
            'Db'     => ['pattern' => 'x46664', 'barre' => 4],

            // ── D family ────────────────────────────────────────────────────
            'D'      => ['pattern' => 'xx0232', 'barre' => null],
            'Dm'     => ['pattern' => 'xx0231', 'barre' => null],
            'D7'     => ['pattern' => 'xx0212', 'barre' => null],
            'Dmaj7'  => ['pattern' => 'xx0222', 'barre' => null],
            'D7M'    => ['pattern' => 'xx0222', 'barre' => null],
            'Dm7'    => ['pattern' => 'xx0211', 'barre' => null],
            'Dadd9'  => ['pattern' => 'xx0230', 'barre' => null],
            'Dsus2'  => ['pattern' => 'xx0230', 'barre' => null],
            'Dsus4'  => ['pattern' => 'xx0233', 'barre' => null],
            'D#'     => ['pattern' => 'x68886', 'barre' => 6],
            'Eb'     => ['pattern' => 'x68886', 'barre' => 6],

            // ── E family ────────────────────────────────────────────────────
            'E'      => ['pattern' => '022100', 'barre' => null],
            'Em'     => ['pattern' => '022000', 'barre' => null],
            'E7'     => ['pattern' => '020100', 'barre' => null],
            'Emaj7'  => ['pattern' => '021100', 'barre' => null],
            'E7M'    => ['pattern' => '021100', 'barre' => null],
            'Em7'    => ['pattern' => '022030', 'barre' => null],
            'Esus4'  => ['pattern' => '022200', 'barre' => null],
            'Eadd9'  => ['pattern' => '024100', 'barre' => null],

            // ── F family ────────────────────────────────────────────────────
            'F'      => ['pattern' => '133211', 'barre' => 1],
            'Fm'     => ['pattern' => '133111', 'barre' => 1],
            'F7'     => ['pattern' => '131211', 'barre' => 1],
            'Fmaj7'  => ['pattern' => 'xx3210', 'barre' => null],
            'F7M'    => ['pattern' => 'xx3210', 'barre' => null],
            'Fm7'    => ['pattern' => '131111', 'barre' => 1],
            'F#'     => ['pattern' => '244322', 'barre' => 2],
            'F#m'    => ['pattern' => '244222', 'barre' => 2],
            'F#7'    => ['pattern' => '242322', 'barre' => 2],
            'F#m7'   => ['pattern' => '242222', 'barre' => 2],
            'F#maj7' => ['pattern' => '243222', 'barre' => 2],
            'F#7M'   => ['pattern' => '243222', 'barre' => 2],
            'Gb'     => ['pattern' => '244322', 'barre' => 2],

            // ── G family ────────────────────────────────────────────────────
            'G'      => ['pattern' => '320003', 'barre' => null],
            'Gm'     => ['pattern' => '355333', 'barre' => 3],
            'G7'     => ['pattern' => '320001', 'barre' => null],
            'Gmaj7'  => ['pattern' => '320002', 'barre' => null],
            'G7M'    => ['pattern' => '320002', 'barre' => null],
            'Gm7'    => ['pattern' => '353333', 'barre' => 3],
            'Gsus4'  => ['pattern' => '320013', 'barre' => null],
            'Gadd9'  => ['pattern' => '320033', 'barre' => null],
            'G#'     => ['pattern' => '466544', 'barre' => 4],
            'Ab'     => ['pattern' => '466544', 'barre' => 4],
            'Abm'    => ['pattern' => '466444', 'barre' => 4],

            // ── A family ────────────────────────────────────────────────────
            'A'      => ['pattern' => 'x02220', 'barre' => null],
            'Am'     => ['pattern' => 'x02210', 'barre' => null],
            'A7'     => ['pattern' => 'x02020', 'barre' => null],
            'Amaj7'  => ['pattern' => 'x02120', 'barre' => null],
            'A7M'    => ['pattern' => 'x02120', 'barre' => null],
            'Am7'    => ['pattern' => 'x02010', 'barre' => null],
            'Asus2'  => ['pattern' => 'x02200', 'barre' => null],
            'Asus4'  => ['pattern' => 'x02230', 'barre' => null],
            'Aadd9'  => ['pattern' => 'x02240', 'barre' => null],
            'A#'     => ['pattern' => 'x13331', 'barre' => 1],
            'Bb'     => ['pattern' => 'x13331', 'barre' => 1],
            'Bbm'    => ['pattern' => 'x13321', 'barre' => 1],
            'Bb7'    => ['pattern' => 'x13131', 'barre' => 1],
            'Bbmaj7' => ['pattern' => 'x13231', 'barre' => 1],
            'Bb7M'   => ['pattern' => 'x13231', 'barre' => 1],

            // ── B family ────────────────────────────────────────────────────
            'B'      => ['pattern' => 'x24442', 'barre' => 2],
            'Bm'     => ['pattern' => 'x24432', 'barre' => 2],
            'B7'     => ['pattern' => 'x21202', 'barre' => null],
            'Bmaj7'  => ['pattern' => 'x24342', 'barre' => 2],
            'B7M'    => ['pattern' => 'x24342', 'barre' => 2],
            'Bm7'    => ['pattern' => 'x24232', 'barre' => 2],

            // ── Slash / inversions ───────────────────────────────────────────
            'G/B'    => ['pattern' => 'x20003', 'barre' => null],
            'G/F#'   => ['pattern' => '200003', 'barre' => null],
            'D/F#'   => ['pattern' => '200232', 'barre' => null],
            'C/G'    => ['pattern' => '332010', 'barre' => null],
            'Am/E'   => ['pattern' => '002210', 'barre' => null],
            'Em/B'   => ['pattern' => 'x22000', 'barre' => null],
            'F/C'    => ['pattern' => 'x33211', 'barre' => null],
        ];
    }

    /**
     * Returns the entry for a single chord name, or null if unknown.
     */
    public static function get(string $chord): ?array
    {
        return static::all()[$chord] ?? null;
    }

    /**
     * Inserts dictionary entries for every chord in $names that is not
     * already present in the chord_diagrams table.
     * Safe to call concurrently (uses firstOrCreate).
     */
    public static function seedMissing(array $names): void
    {
        $dict = static::all();

        foreach (array_unique($names) as $name) {
            if (! isset($dict[$name])) {
                continue;
            }

            ChordDiagram::firstOrCreate(
                ['name' => $name],
                [
                    'strings_pattern' => $dict[$name]['pattern'],
                    'barre'           => $dict[$name]['barre'],
                ]
            );
        }
    }
}
