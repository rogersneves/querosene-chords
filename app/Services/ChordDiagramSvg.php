<?php

namespace App\Services;

/**
 * Renders a guitar chord diagram for DomPDF.
 *
 * Each diagram is wrapped in display:inline-block so multiple diagrams
 * flow side by side in the PDF. Dots use background-color (not Unicode
 * characters) because DomPDF's embedded fonts lack U+25CF.
 *
 * Pattern: 6 chars, low-E to high-e. x=muted, 0=open, 1–9=fret.
 */
class ChordDiagramSvg
{
    public static function render(string $name, string $pattern, ?int $barre): string
    {
        $chars = str_split($pattern);

        $usedFrets = [];
        foreach ($chars as $c) {
            if (is_numeric($c) && (int)$c > 0) {
                $usedFrets[] = (int)$c;
            }
        }

        $maxFret   = empty($usedFrets) ? 0 : max($usedFrets);
        $startFret = ($maxFret <= 4) ? 1 : min($usedFrets);
        $isOpen    = ($startFret === 1);

        $W  = '11pt';
        $FH = '11pt';
        $lB  = 'border-left:0.8pt solid #bbb;';
        $lrB = 'border-left:0.8pt solid #bbb;border-right:0.8pt solid #bbb;';

        // inline-block wrapper: makes diagrams flow horizontally; avoid splits across pages
        $t = '<div style="display:inline-block;vertical-align:top;margin-right:7pt;page-break-inside:avoid;">';
        $t .= '<table style="border-collapse:collapse;width:66pt;">';

        // ── Chord name ─────────────────────────────────────────
        $t .= '<tr><td colspan="6" style="font-family:Arial,sans-serif;font-size:8pt;font-weight:bold;'
            . 'color:#e65c00;text-align:center;padding:0 0 3pt 0;line-height:1.3;">'
            . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
            . '</td></tr>';

        // ── x / o indicators ───────────────────────────────────
        $t .= '<tr>';
        foreach ($chars as $c) {
            $label = ($c === 'x') ? 'x' : (($c === '0') ? 'o' : '');
            $t .= '<td style="font-family:Arial,sans-serif;width:' . $W . ';height:8pt;'
                . 'text-align:center;vertical-align:bottom;font-size:6.5pt;color:#777;padding:0;line-height:1;">'
                . $label . '</td>';
        }
        $t .= '</tr>';

        // ── Nut ────────────────────────────────────────────────
        if ($isOpen) {
            $t .= '<tr><td colspan="6" style="height:3pt;background-color:#1a1a1a;padding:0;font-size:0;line-height:0;"></td></tr>';
        } else {
            $t .= '<tr>';
            foreach ($chars as $i => $c) {
                $borders = ($i === 5 ? $lrB : $lB) . 'border-top:0.6pt solid #bbb;';
                $t .= '<td style="' . $borders . 'width:' . $W . ';height:2pt;padding:0;font-size:0;"></td>';
            }
            $t .= '</tr>';
        }

        // ── Fret rows ──────────────────────────────────────────
        for ($f = 1; $f <= 4; $f++) {
            $cur        = $startFret + $f - 1;
            $isBarreRow = ($barre !== null && $cur === $barre);

            $t .= '<tr>';
            foreach ($chars as $i => $c) {
                $borders = ($i === 5 ? $lrB : $lB) . 'border-bottom:0.5pt solid #ddd;';
                $fretNum = is_numeric($c) ? (int)$c : 0;
                $hasDot  = ($fretNum === $cur);
                $fill    = $hasDot || ($isBarreRow && $c !== 'x' && $c !== '0');

                // Use background-color for the dot — &#9679; (●) is not in DomPDF font encoding
                if ($fill) {
                    $t .= '<td style="' . $borders . 'width:' . $W . ';height:' . $FH . ';'
                        . 'background-color:#1a1a1a;padding:0;">&nbsp;</td>';
                } else {
                    $t .= '<td style="' . $borders . 'width:' . $W . ';height:' . $FH . ';padding:0;"></td>';
                }
            }
            $t .= '</tr>';
        }

        // ── Fret position label (non-open) ─────────────────────
        if (!$isOpen) {
            $t .= '<tr><td colspan="6" style="font-family:Arial,sans-serif;font-size:6pt;'
                . 'color:#888;text-align:left;padding:1pt 0 0 1pt;line-height:1;">'
                . $startFret . 'a</td></tr>';
        }

        $t .= '</table>';
        $t .= '</div>';
        return $t;
    }
}
