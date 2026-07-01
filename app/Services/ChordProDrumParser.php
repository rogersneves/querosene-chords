<?php

namespace App\Services;

class ChordProDrumParser
{
    /**
     * Parseia o conteúdo ChordPro e retorna os dados para o drum player.
     *
     * bars_map: cada entrada aponta para o índice sequencial (0-based) da
     * linha de acordes entre todas as .cp-line que contêm data-chord no DOM.
     * O JS em songPlayer.init() numera essas linhas com data-line-index.
     */
    public function parse(string $content): array
    {
        $lines     = explode("\n", str_replace("\r\n", "\n", $content));
        $bpm       = 120;
        $genre     = 'rock';
        $drumStyle = null;

        $barsMap         = [];
        $drumHints       = [];
        $currentSection  = null;
        $sectionBarCount = 0;
        $chordLineIdx    = 0; // índice entre todas as linhas de acorde no DOM

        foreach ($lines as $line) {
            $line = rtrim($line);

            // ── Metadados ────────────────────────────────────────────────
            if (preg_match('/^\{tempo:\s*(\d+)\}/', $line, $m)) {
                $bpm = (int) $m[1];
                continue;
            }
            if (preg_match('/^\{genre:\s*(.+?)\}/', $line, $m)) {
                $genre = trim($m[1]);
                continue;
            }
            if (preg_match('/^\{drum_style:\s*(.+?)\}/', $line, $m)) {
                $drumStyle = trim($m[1]);
                continue;
            }

            // ── Diretivas drum ───────────────────────────────────────────
            if (preg_match('/^\{drum_intro:\s*(\d+)\}/', $line, $m)) {
                $drumHints[] = ['section' => $currentSection, 'pattern' => 'intro', 'bars' => (int) $m[1]];
                continue;
            }
            if (preg_match('/^\{drum_fill\}/', $line)) {
                $drumHints[] = ['section' => $currentSection, 'pattern' => 'fill', 'bars' => 1];
                continue;
            }
            if (preg_match('/^\{drum_outro:\s*(\d+)\}/', $line, $m)) {
                $drumHints[] = ['section' => $currentSection, 'pattern' => 'outro', 'bars' => (int) $m[1]];
                continue;
            }

            // ── Início de seção ──────────────────────────────────────────
            if (preg_match('/^\{start_of_(\w+)/', $line, $m)) {
                $currentSection  = $m[1];
                $sectionBarCount = 0;
                continue;
            }

            // ── Fim de seção ─────────────────────────────────────────────
            if (preg_match('/^\{end_of_\w+\}/', $line)) {
                $hasHint = collect($drumHints)->where('section', $currentSection)->isNotEmpty();

                if (!$hasHint && $currentSection && $sectionBarCount > 0) {
                    $drumHints[] = [
                        'section' => $currentSection,
                        'pattern' => 'main',
                        'bars'    => $sectionBarCount,
                    ];
                }

                $currentSection  = null;
                $sectionBarCount = 0;
                continue;
            }

            // ── Linha de acordes (compasso) ──────────────────────────────
            if ($currentSection && preg_match('/\[[A-G][#b]?[^\]]*\]/', $line)) {
                $barsCount = 1;
                if (preg_match('/\{bars:\s*(\d+)\}/', $line, $bm)) {
                    $barsCount = max(1, (int) $bm[1]);
                }
                for ($b = 0; $b < $barsCount; $b++) {
                    $barsMap[] = [
                        'bar'        => count($barsMap) + 1,
                        'section'    => $currentSection,
                        'line_index' => $chordLineIdx,
                    ];
                    $sectionBarCount++;
                }
                $chordLineIdx++;
            }
        }

        return compact('bpm', 'genre', 'drumStyle', 'barsMap', 'drumHints');
    }
}
