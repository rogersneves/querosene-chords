<?php

namespace App\Services;

/**
 * Renders a guitar chord diagram as an inline SVG element.
 * Designed for server-side use in PDF exports — no JavaScript required.
 *
 * Pattern format (ChordDictionary): 6 chars, low-E to high-e.
 *   x = muted | 0 = open | 1-9 = fret number
 */
class ChordDiagramSvg
{
    private const SX    = 10;   // x of first string (low-E)
    private const SG    = 10;   // horizontal string gap
    private const NUT_Y = 27;   // y of nut (top grid line)
    private const FG    = 12;   // vertical fret gap
    private const IND_Y = 18;   // y center for x/o indicators
    private const DOT_R = 4;    // finger dot radius

    public static function render(string $name, string $pattern, ?int $barre): string
    {
        $chars = str_split($pattern);

        // Fret numbers used (excluding 0=open)
        $usedFrets = [];
        foreach ($chars as $c) {
            if (is_numeric($c) && (int)$c > 0) {
                $usedFrets[] = (int)$c;
            }
        }

        $maxFret   = empty($usedFrets) ? 0 : max($usedFrets);
        $startFret = ($maxFret <= 4) ? 1 : min($usedFrets);
        $isOpen    = ($startFret === 1);

        $gridLeft  = self::SX;
        $gridRight = self::SX + 5 * self::SG;
        $nutY      = self::NUT_Y;
        $botY      = $nutY + 4 * self::FG;

        $svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 70 80" width="60" height="69">';
        $svg .= '<rect width="70" height="80" fill="white"/>';

        // Chord name
        $svg .= sprintf(
            '<text x="35" y="11" text-anchor="middle" font-family="Arial,sans-serif" font-size="9" font-weight="bold" fill="#1a1a1a">%s</text>',
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
        );

        // x/o indicators above nut
        foreach ($chars as $i => $c) {
            $sx = self::SX + $i * self::SG;
            if ($c === 'x') {
                $svg .= sprintf(
                    '<text x="%d" y="%d" text-anchor="middle" font-family="Arial,sans-serif" font-size="8" fill="#666">×</text>',
                    $sx, self::IND_Y + 3
                );
            } elseif ($c === '0') {
                $svg .= sprintf(
                    '<circle cx="%d" cy="%d" r="2.5" fill="none" stroke="#666" stroke-width="1.2"/>',
                    $sx, self::IND_Y
                );
            }
        }

        // Nut line
        if ($isOpen) {
            // Thick nut
            $svg .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="3" fill="#1a1a1a"/>',
                $gridLeft, $nutY, $gridRight - $gridLeft
            );
        } else {
            // Thin top line + fret position label
            $svg .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#bbb" stroke-width="0.8"/>',
                $gridLeft, $nutY, $gridRight, $nutY
            );
            $svg .= sprintf(
                '<text x="%d" y="%d" text-anchor="end" font-family="Arial,sans-serif" font-size="7" fill="#666">%d</text>',
                $gridLeft - 2, $nutY + 5, $startFret
            );
        }

        // Fret lines (4 rows)
        for ($f = 1; $f <= 4; $f++) {
            $fy = $nutY + $f * self::FG;
            $svg .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#bbb" stroke-width="0.8"/>',
                $gridLeft, $fy, $gridRight, $fy
            );
        }

        // String lines (vertical)
        $strTop = $isOpen ? $nutY + 3 : $nutY;
        for ($i = 0; $i < 6; $i++) {
            $sx = self::SX + $i * self::SG;
            $svg .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#aaa" stroke-width="0.8"/>',
                $sx, $strTop, $sx, $botY
            );
        }

        // Barre bar
        if ($barre !== null) {
            $relFret = $barre - $startFret + 1;
            if ($relFret >= 1 && $relFret <= 4) {
                $barreY = $nutY + $relFret * self::FG - self::FG / 2;

                $nonMuted = array_keys(array_filter($chars, fn($c) => $c !== 'x'));
                if (!empty($nonMuted)) {
                    $bl = self::SX + min($nonMuted) * self::SG;
                    $br = self::SX + max($nonMuted) * self::SG;
                    $svg .= sprintf(
                        '<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="#1a1a1a" stroke-width="8" stroke-linecap="round"/>',
                        $bl, $barreY, $br, $barreY
                    );
                }
            }
        }

        // Finger dots
        foreach ($chars as $i => $c) {
            if (!is_numeric($c) || (int)$c === 0) {
                continue;
            }
            $fret    = (int)$c;
            $relFret = $fret - $startFret + 1;
            if ($relFret < 1 || $relFret > 4) {
                continue;
            }
            $dotX = self::SX + $i * self::SG;
            $dotY = $nutY + $relFret * self::FG - self::FG / 2;
            $svg .= sprintf(
                '<circle cx="%d" cy="%.1f" r="%d" fill="#1a1a1a"/>',
                $dotX, $dotY, self::DOT_R
            );
        }

        $svg .= '</svg>';
        return $svg;
    }
}
