<?php

namespace App\Services;

class ChordProRenderer
{
    /**
     * Filtra linhas {comment} pelo idioma ativo.
     *
     * {comment: [PT] texto} → exibe só se locale = 'pt', remove o prefixo [PT]
     * {comment: texto sem prefixo} → sempre exibe, sem alteração
     */
    public function filterCommentsByLocale(string $content, string $locale): string
    {
        $supported = ['PT', 'EN', 'ES', 'FR'];
        $active    = strtoupper($locale);

        $filtered = array_map(function (string $line) use ($supported, $active) {
            if (!preg_match('/^\{comment:\s*\[([A-Z]{2})\]\s*(.*?)\}$/s', $line, $m)) {
                return $line;
            }

            $lang = $m[1];
            $text = trim($m[2]);

            if (in_array($lang, $supported) && $lang !== $active) {
                return null;
            }

            return '{comment: ' . $text . '}';
        }, explode("\n", $content));

        return implode("\n", array_filter($filtered, fn($l) => $l !== null));
    }

    /**
     * Retorna a cifra como lista de seções com seus compassos para o chord grid.
     */
    public function toGrid(string $content, string $locale = 'pt'): array
    {
        $content     = $this->filterCommentsByLocale($content, $locale);
        $lines       = explode("\n", str_replace("\r\n", "\n", $content));
        $sections    = [];
        $current     = null;
        $barIndex    = 0;
        $pendingFill = false;
        $commentIdx  = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^\{start_of_(\w+)(?::\s*(.+?))?\}/', $line, $m)) {
                if ($current) $sections[] = $current;
                $current = [
                    'section_type'  => $m[1],
                    'section_label' => trim($m[2] ?? ucfirst($m[1])),
                    'bars'          => [],
                ];
                continue;
            }

            if (preg_match('/^\{end_of_\w+\}/', $line)) {
                if ($current) { $sections[] = $current; $current = null; }
                continue;
            }

            if (preg_match('/^\{drum_fill\}/', $line)) { $pendingFill = true; continue; }

            if (preg_match('/^\{(?:comment|c)(?::\s*(.*))?\}$/i', $line, $cm)) {
                $text = trim($cm[1] ?? '');
                if ($text !== '') {
                    if (!$current) {
                        $current = ['section_type' => 'verse', 'section_label' => '', 'bars' => []];
                    }
                    $current['bars'][] = ['type' => 'comment', 'text' => $text, 'cid' => $commentIdx++];
                }
                continue;
            }

            if (preg_match('/^\{/', $line)) { continue; }

            if ($current && preg_match_all('/\[([^\]]+)\]/', $line, $matches)) {
                $chords = array_values(array_filter($matches[1], fn($c) => $this->isChordToken($c)));
                if ($chords) {
                    $barsCount = 1;
                    if (preg_match('/\{bars:\s*(\d+)\}/', $line, $bm)) {
                        $barsCount = max(1, (int) $bm[1]);
                    }
                    for ($b = 0; $b < $barsCount; $b++) {
                        $current['bars'][] = [
                            'index'      => $barIndex++,
                            'chords'     => $chords,
                            'is_fill'    => $pendingFill && $b === $barsCount - 1,
                            'bars_count' => $barsCount,
                            'bar_offset' => $b,
                        ];
                    }
                    $pendingFill = false;
                }
            }
        }

        if ($current) $sections[] = $current;

        return $sections;
    }

    public function render(string $content, ?string $locale = null): string
    {
        $locale  = $locale ?? app()->getLocale();
        $content = $this->filterCommentsByLocale($content, $locale);

        $lines        = explode("\n", str_replace("\r\n", "\n", $content));
        $count        = count($lines);
        $html         = '';
        $inSection    = false;
        $inTabSection = false;
        $lastWasComment = false;

        for ($i = 0; $i < $count; $i++) {
            $line = rtrim($lines[$i]);

            // ── Directive: {name} or {name: label} ────────────────────────────
            if (preg_match('/^\{([^}]+)\}$/', $line, $m)) {
                $directive = trim($m[1]);

                if (preg_match('/^start_of_(\w+)(?::\s*(.+))?$/i', $directive, $dm)) {
                    if ($inSection) {
                        $html .= "</div>\n";
                        $inTabSection = false; // Reset tab flag when closing previous section
                    }

                    $suffix = strtolower($dm[1]);
                    $type   = match (true) {
                        str_contains($suffix, 'chorus') => 'chorus',
                        str_contains($suffix, 'bridge') => 'bridge',
                        str_contains($suffix, 'tab') || str_contains($suffix, 'grid') => 'tab',
                        default => 'verse',
                    };

                    $label = $dm[2] ?? match ($type) {
                        'chorus' => 'Refrão',
                        'bridge' => 'Ponte',
                        'tab'    => 'Tablatura',
                        default  => ucfirst($suffix),
                    };

                    $html .= "<div class=\"cp-section cp-section-{$type}\">\n";
                    $html .= '<span class="cp-section-label">' . $this->e($label) . "</span>\n";
                    $inSection      = true;
                    $inTabSection   = ($type === 'tab');
                    $lastWasComment = false;
                    continue;
                }

                if (preg_match('/^end_of_/i', $directive)) {
                    if ($inSection) {
                        $html .= "</div>\n";
                        $inSection = false;
                        $inTabSection = false;
                    }
                    continue;
                }

                // Comment directive {c: text} or {comment: text}
                if (preg_match('/^(?:comment|c)(?::\s*(.*))?$/i', $directive, $cm)) {
                    $comment = trim($cm[1] ?? '');
                    if ($comment === '') {
                        $html .= "<div class=\"cp-spacer\"></div>\n";
                        $lastWasComment = false;
                    } else {
                        $html .= '<div class="cp-comment">' . $this->e($comment) . "</div>\n";
                        $lastWasComment = true;
                    }
                    continue;
                }

                // Other directives (title, artist, key, capo…) — skip
                continue;
            }

            // ── ChordPro comment (#) or suppressed plain-text line ───────────
            if (str_starts_with(ltrim($line), '#') || $this->isSuppressedLine($line)) {
                continue; // não altera $lastWasComment — linha invisível
            }

            // ── Empty line ────────────────────────────────────────────────────
            if (trim($line) === '') {
                // Suprimir spacer entre linhas de comment consecutivas
                if ($lastWasComment) {
                    $nextReal = '';
                    for ($j = $i + 1; $j < $count; $j++) {
                        $nxt = trim($lines[$j]);
                        if ($nxt !== '') { $nextReal = $nxt; break; }
                    }
                    if (preg_match('/^\{(?:comment|c):/i', $nextReal)) {
                        continue;
                    }
                }
                $html .= "<div class=\"cp-spacer\"></div>\n";
                $lastWasComment = false;
                continue;
            }

            // ── Tablature block: E|--- B|--- G|--- D|--- A|--- E|--- ─────────
            // Collect consecutive tab lines (and blank lines between two systems)
            // into a single <pre> block.
            if (preg_match('/^[EBGDAe]\|/', $line)) {
                $tabLines = [];
                while ($i < $count) {
                    $tl = rtrim($lines[$i]);
                    if (preg_match('/^[EBGDAe]\|/', $tl)) {
                        $tabLines[] = $tl;
                        $i++;
                    } elseif (
                        trim($tl) === '' &&
                        isset($lines[$i + 1]) &&
                        preg_match('/^[EBGDAe]\|/', rtrim($lines[$i + 1]))
                    ) {
                        $tabLines[] = '';
                        $i++;
                    } else {
                        break;
                    }
                }
                $i--; // for-loop increment will move past the last consumed line
                $html .= '<pre class="cp-tab">' . $this->e(implode("\n", $tabLines)) . "</pre>\n";
                continue;
            }

            // ── Plain-text chord-only line (e.g. "D7M  G7M  D7M  G7M") ──────
            // These appear when the importer stored the raw chord line before
            // the [bracket] version. Skip if the next line is already the
            // ChordPro [bracket] form; otherwise render as orange chord tokens.
            if (! str_contains($line, '[') && $this->isChordOnlyLine($line)) {
                $next = ($i + 1 < $count) ? rtrim($lines[$i + 1]) : '';
                if (str_contains($next, '[')) {
                    // Next line is the proper ChordPro version — skip this one
                    continue;
                }
                $html .= $this->renderChordOnlyLine($line);
                continue;
            }

            // ── Annotation line: "Label: [Ch] [Ch] text" ─────────────────────
            // Detects lines that start with a label ending in ":" followed by chords
            // e.g. "Intro: [Am] [Dm] x 2" — renders chords inline instead of above
            if (str_contains($line, '[') &&
                preg_match('/^([^[\]{}]+:)\s*(.+)$/u', $line, $annm) &&
                str_contains($annm[2], '[')) {
                $html .= $this->renderAnnotationLine(rtrim($annm[1]), $annm[2]);
                continue;
            }

            // ── Regular line (may have inline [Chord] markers) ────────────────
            $lastWasComment = false;
            $html .= $this->renderLine($line, $inTabSection);
        }

        if ($inSection) {
            $html .= "</div>\n";
        }

        return $html;
    }

    // ── Plain-text chord detection ───────────────────────────────────────────

    /**
     * Returns true when every non-empty whitespace-separated token in $line
     * is a valid chord name or an annotation like "(x2)".
     */
    private function isChordOnlyLine(string $line): bool
    {
        $trimmed = trim($line);
        if ($trimmed === '') {
            return false;
        }

        foreach (preg_split('/\s+/', $trimmed) as $token) {
            if ($token !== '' && ! $this->isChordToken($token)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true for individual chord tokens, including:
     * - Standard chord names: C, D#, Bm, F#m, G7, Am7, Dsus4, D7M, G7M
     * - Slash chords: C/G, Am/E
     * - Repetition annotations: (x2), x3, 2x
     */
    private function isChordToken(string $token): bool
    {
        if ($token === '') {
            return true;
        }

        // Repetition markers: (x2), x2, 2x, (3x) …
        if (preg_match('/^\(?\d*x\d*\)?$/i', $token)) {
            return true;
        }

        // Chord name (handles Brazilian "D7M / G7M" notation):
        //   root [A-G] + optional accidental [#b]
        //   + optional quality (m, M, dim, aug, sus…)
        //   + optional numeric modifier [0-9]*
        //   + optional trailing M (major — as in 7M)
        //   + optional bass note /X
        return (bool) preg_match(
            '/^[A-G][#b]?(?:°|m(?:aj)?|M(?:aj)?|dim|aug|sus[24]?|add[0-9]*)?[0-9]*M?(?:\([^)]+\))?(?:\/[A-G][#b]?)?$/u',
            $token
        );
    }

    /** Renders a plain-text chord-only line as orange chord spans. */
    private function renderChordOnlyLine(string $line): string
    {
        $html = '<div class="cp-line">';

        foreach (preg_split('/\s+/', trim($line)) as $token) {
            if ($token === '') {
                continue;
            }
            $html .= '<span class="cp-pair">'
                . '<span class="cp-chord" data-chord="' . $this->e($token) . '">' . $this->e($token) . '</span>'
                . '<span class="cp-lyric"> </span>'
                . '</span>';
        }

        $html .= "</div>\n";
        return $html;
    }

    // ── ChordPro [bracket] line rendering ────────────────────────────────────

    /** Renders "Intro: [Am] [Dm] x 2" as inline chord row with a label. */
    private function renderAnnotationLine(string $label, string $rest): string
    {
        $html = '<div class="cp-line cp-annotation">'
            . '<span class="cp-annotation-label">' . $this->e($label) . '</span>';

        $parts = preg_split('/(\[[^\]]+\])/', $rest, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $part) {
            if (preg_match('/^\[([^\]]+)\]$/', $part, $m)) {
                $chord = $m[1];
                $html .= '<span class="cp-chord" data-chord="' . $this->e($chord) . '">'
                    . $this->e($chord) . '</span>';
            } elseif (trim($part) !== '') {
                $html .= '<span class="cp-annotation-suffix">' . $this->e(trim($part)) . '</span>';
            }
        }

        $html .= "</div>\n";
        return $html;
    }

    private function renderLine(string $line, bool $inTabSection = false): string
    {
        // In tab sections, strip chord markers and render only the lyric text
        if ($inTabSection && str_contains($line, '[')) {
            $line = preg_replace('/\[[^\]]+\]/', '', $line);
        }

        if (! str_contains($line, '[')) {
            // Plain lyric line with no chords
            return '<div class="cp-line"><span class="cp-pair">'
                . '<span class="cp-chord"></span>'
                . '<span class="cp-lyric">' . $this->e($line) . '</span>'
                . "</span></div>\n";
        }

        // Split on chord markers, keeping delimiters
        $parts = preg_split('/(\[[^\]]+\])/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);

        $html         = '<div class="cp-line">';
        $currentChord = '';

        foreach ($parts as $part) {
            if (preg_match('/^\[([^\]]+)\]$/', $part, $m)) {
                if ($this->isChordToken($m[1])) {
                    $currentChord = $m[1];
                } else {
                    // Bracketed text that isn't a chord — render as lyric
                    $chordAttr = $currentChord !== ''
                        ? ' data-chord="' . $this->e($currentChord) . '"'
                        : '';
                    $html .= '<span class="cp-pair">'
                        . '<span class="cp-chord"' . $chordAttr . '>' . $this->e($currentChord) . '</span>'
                        . '<span class="cp-lyric">' . $this->e($m[1]) . '</span>'
                        . '</span>';
                    $currentChord = '';
                }
            } else {
                // Skip empty leading fragment with no pending chord
                if ($currentChord === '' && $part === '') {
                    continue;
                }

                $chordAttr = $currentChord !== ''
                    ? ' data-chord="' . $this->e($currentChord) . '"'
                    : '';

                $html .= '<span class="cp-pair">'
                    . '<span class="cp-chord"' . $chordAttr . '>' . $this->e($currentChord) . '</span>'
                    . '<span class="cp-lyric">' . $this->e($part !== '' ? $part : ' ') . '</span>'
                    . '</span>';

                $currentChord = '';
            }
        }

        // Chord at end of line with no trailing lyric text
        if ($currentChord !== '') {
            $html .= '<span class="cp-pair">'
                . '<span class="cp-chord" data-chord="' . $this->e($currentChord) . '">' . $this->e($currentChord) . '</span>'
                . '<span class="cp-lyric"> </span>'
                . '</span>';
        }

        $html .= "</div>\n";
        return $html;
    }

    /**
     * Lines that carry metadata or external references and must not be
     * rendered as lyrics or chords:
     *   - Standalone URLs (YouTube links stored without braces)
     *   - Plain-text capo / tempo / key / tuning annotations
     */
    private function isSuppressedLine(string $line): bool
    {
        $t = trim($line);

        if ($t === '') {
            return false;
        }

        // Standalone URL on its own line
        if (preg_match('/^https?:\/\/\S+$/i', $t)) {
            return true;
        }

        // Plain-text metadata annotations: "Capo 4", "Capo: 4", "Key: Am",
        // "Tempo: 120", "Tuning: EADGBE", etc.
        if (preg_match('/^(?:capo|key|tempo|bpm|tuning|time)\s*:?\s*\S/i', $t)) {
            return true;
        }

        return false;
    }

    private function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
