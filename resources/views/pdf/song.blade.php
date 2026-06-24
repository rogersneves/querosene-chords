<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    color: #1a1a1a;
    background: white;
    line-height: 1.3;
    margin: 1.8cm 2.2cm;
}

/* ── Header ─────────────────────────────────────────── */
.song-header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}
.song-header-main { vertical-align: top; padding: 0; }
.song-title  { font-size: 17pt; font-weight: bold; line-height: 1.1; margin-bottom: 3pt; }
.song-artist { font-size: 10.5pt; color: #444; margin-bottom: 5pt; }
.song-meta   { font-size: 8pt; color: #999; line-height: 1.4; }
.song-header-key {
    width: 52pt;
    vertical-align: middle;
    text-align: center;
    border: 2pt solid #FF6D00;
    font-size: 15pt;
    font-weight: bold;
    color: #e65c00;
    padding: 4pt 0;
    line-height: 1;
}
.header-rule {
    height: 2pt;
    background-color: #FF6D00;
    margin: 6pt 0 14pt 0;
    font-size: 0;
    line-height: 0;
}

/* ── ChordPro content ───────────────────────────────── */
.cp-content { margin-bottom: 16pt; }

.cp-line {
    display: block;
    margin-bottom: 2pt;
    white-space: nowrap;
    overflow: hidden;
    line-height: 1;
}
.cp-pair {
    display: inline-block;
    vertical-align: bottom;
    margin-right: 1pt;
    min-width: 6pt;
}
.cp-chord {
    display: block;
    font-size: 8.5pt;
    font-weight: bold;
    color: #e65c00;
    min-height: 11pt;
    line-height: 1;
}
.cp-lyric {
    display: block;
    font-size: 10pt;
    line-height: 1.25;
    white-space: pre;
}
.cp-spacer { height: 7pt; display: block; }

.cp-section {
    border-left: 3pt solid #FF6D00;
    padding: 5pt 8pt;
    margin: 8pt 0;
    background: #fffbf8;
    page-break-inside: avoid;
}
.cp-section-chorus { background: #fff9f7; }
.cp-section-bridge { border-left-color: #cc8800; background: #fffef5; }
.cp-section-tab    { border-left-color: #999; background: #f8f8f8; }

.cp-section-label {
    display: block;
    font-size: 7pt;
    font-weight: bold;
    color: #e65c00;
    text-transform: uppercase;
    letter-spacing: 0.4pt;
    margin-bottom: 4pt;
}
.cp-section-bridge .cp-section-label { color: #aa6600; }
.cp-section-tab    .cp-section-label { color: #777; }

.cp-tab {
    font-family: "Courier New", Courier, monospace;
    font-size: 7.5pt;
    line-height: 1.4;
    white-space: pre;
}
.cp-comment { font-style: italic; color: #777; font-size: 9pt; margin: 3pt 0; }

.cp-annotation { display: block; font-size: 9pt; margin-bottom: 2pt; }
.cp-annotation-label  { font-weight: bold; color: #555; margin-right: 4pt; }
.cp-annotation .cp-chord { display: inline; font-size: 9pt; font-weight: bold; color: #e65c00; margin: 0 2pt; }
.cp-annotation-suffix { color: #777; margin-left: 2pt; }

/* ── Chord diagrams ─────────────────────────────────── */
.diagrams-section {
    border-top: 1pt solid #e8e8e8;
    padding-top: 10pt;
    margin-top: 10pt;
    page-break-inside: avoid;
}
.diagrams-title {
    font-size: 7pt;
    font-weight: bold;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    margin-bottom: 7pt;
}

/* ── Footer ─────────────────────────────────────────── */
.pdf-watermark {
    margin-top: 16pt;
    padding-top: 5pt;
    border-top: 0.5pt solid #eee;
    text-align: center;
    font-size: 6.5pt;
    color: #ccc;
}
</style>
</head>
<body>

{{-- ── Header ────────────────────────────────────────────── --}}
<table class="song-header-table">
    <tr>
        <td class="song-header-main">
            <div class="song-title">{{ $song->title }}</div>
            <div class="song-artist">{{ $song->artist->name }}</div>
            <div class="song-meta">
                @php
                    $meta = [];
                    if ($song->album)      $meta[] = $song->album;
                    if ($song->year)       $meta[] = (string) $song->year;
                    if ($song->bpm)        $meta[] = $song->bpm . ' BPM';
                    if ($song->difficulty) {
                        $diffs = ['iniciante'=>'Iniciante','intermediário'=>'Intermediário','avançado'=>'Avançado'];
                        $meta[] = $diffs[$song->difficulty] ?? ucfirst($song->difficulty);
                    }
                @endphp
                {{ implode(' · ', $meta) }}
            </div>
        </td>
        @if($song->key)
        <td class="song-header-key">{{ $song->key }}</td>
        @endif
    </tr>
</table>
<div class="header-rule"></div>

{{-- ── Conteúdo ChordPro ──────────────────────────────────── --}}
<div class="cp-content">
    {!! $html !!}
</div>

{{-- ── Diagramas de acordes ──────────────────────────────── --}}
@if(!empty($diagrams))
<div class="diagrams-section">
    <div class="diagrams-title">Diagramas de acordes</div>
    @foreach($diagrams as $svg)
    {!! $svg !!}
    @endforeach
</div>
@endif

<div class="pdf-watermark">querosene.test &mdash; Dê um gás na sua música</div>

</body>
</html>
