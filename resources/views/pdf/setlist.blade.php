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

/* ── Capa ───────────────────────────────────────────── */
.cover-page {
    page-break-after: always;
    text-align: center;
    padding-top: 20pt;
}

.cover-logo {
    margin-bottom: 44pt;
}
.cover-logo-q {
    font-size: 32pt;
    font-weight: 900;
    color: #FF6D00;
    line-height: 1;
}
.cover-logo-rest {
    font-size: 20pt;
    font-weight: 900;
    color: #1a1a1a;
    line-height: 1;
}
.cover-logo-chords {
    font-size: 14pt;
    font-weight: bold;
    color: #FF6D00;
    margin-left: 4pt;
    line-height: 1;
}
.cover-tagline {
    font-size: 8pt;
    color: #bbb;
    margin-top: 5pt;
    letter-spacing: 0.5pt;
}

.cover-name {
    font-size: 22pt;
    font-weight: 900;
    color: #1a1a1a;
    border-bottom: 2.5pt solid #FF6D00;
    display: inline-block;
    padding-bottom: 7pt;
    margin-bottom: 8pt;
    line-height: 1.2;
}
.cover-count {
    font-size: 9pt;
    color: #999;
    margin-bottom: 28pt;
}

/* ── Índice ─────────────────────────────────────────── */
.toc-title {
    font-size: 7.5pt;
    font-weight: bold;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 0.6pt;
    margin-bottom: 8pt;
    text-align: left;
}
.toc-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}
.toc-col { width: 50%; vertical-align: top; padding: 0; }
.toc-col-right { padding-left: 14pt; }

.toc-item {
    border-bottom: 0.5pt solid #f0f0f0;
    padding: 3pt 0;
    line-height: 1.2;
}
.toc-num {
    font-size: 7.5pt;
    font-weight: bold;
    color: #e65c00;
    display: inline;
    margin-right: 3pt;
}
.toc-song {
    font-size: 8pt;
    font-weight: bold;
    color: #1a1a1a;
    display: inline;
}
.toc-key {
    display: inline-block;
    width: 16pt;
    font-size: 7.5pt;
    font-family: "Courier New", monospace;
    font-weight: bold;
    color: #e65c00;
    vertical-align: bottom;
}
.toc-artist {
    font-size: 7pt;
    color: #999;
    display: block;
    margin-left: 14pt;
    margin-top: 1pt;
}

/* ── Página de cifra ─────────────────────────────────── */
.song-page { page-break-after: always; padding-top: 1.8cm; }
.song-page:last-child { page-break-after: auto; }

.song-header-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.song-header-main  { vertical-align: top; padding: 0; }

.song-num    { font-size: 7pt; color: #e65c00; font-weight: bold; margin-bottom: 2pt; }
.song-title  { font-size: 15pt; font-weight: bold; line-height: 1.1; margin-bottom: 2pt; }
.song-artist { font-size: 9.5pt; color: #444; margin-bottom: 3pt; }
.song-meta   { font-size: 7.5pt; color: #999; }

.song-header-key {
    width: 46pt;
    vertical-align: middle;
    text-align: center;
    border: 1.5pt solid #FF6D00;
    font-size: 13pt;
    font-weight: bold;
    color: #e65c00;
    padding: 4pt 0;
    line-height: 1;
}

.header-rule {
    height: 1.5pt;
    background-color: #FF6D00;
    margin: 5pt 0 10pt 0;
    font-size: 0;
    line-height: 0;
}

/* ── ChordPro content ───────────────────────────────── */
.cp-content { margin-bottom: 14pt; }

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
.cp-spacer { height: 6pt; display: block; }

.cp-section {
    border-left: 3pt solid #FF6D00;
    padding: 4pt 7pt;
    margin: 6pt 0;
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
    margin-bottom: 3pt;
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
    padding-top: 8pt;
    margin-top: 8pt;
    page-break-inside: avoid;
}
.diagrams-title {
    font-size: 7pt;
    font-weight: bold;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    margin-bottom: 6pt;
}

/* ── Footer ─────────────────────────────────────────── */
.pdf-watermark {
    margin-top: 12pt;
    padding-top: 5pt;
    border-top: 0.5pt solid #eee;
    text-align: center;
    font-size: 6.5pt;
    color: #ccc;
}
</style>
</head>
<body>

{{-- ── Capa ─────────────────────────────────────────────── --}}
<div class="cover-page">

    {{-- Logo --}}
    <div class="cover-logo">
        <div>
            <span class="cover-logo-q">Q</span><span
            class="cover-logo-rest">uerosene</span><span
            class="cover-logo-chords">Chords</span>
        </div>
        <div class="cover-tagline">Dê um gás na sua música</div>
    </div>

    {{-- Nome do caderno --}}
    <div class="cover-name">{{ $setlist->name }}</div>
    <div class="cover-count">
        {{ $songs->count() }} {{ $songs->count() === 1 ? 'música' : 'músicas' }}
    </div>

    {{-- Índice (2 colunas) --}}
    <div class="toc-title">Índice</div>
    @php
        $total   = $songs->count();
        $half    = (int) ceil($total / 2);
        $col1    = $songs->values()->slice(0, $half);
        $col2    = $songs->values()->slice($half);
    @endphp
    <table class="toc-table">
        <tr>
            <td class="toc-col">
                @foreach($col1 as $entry)
                @php $s = $entry['song']; @endphp
                <div class="toc-item">
                    <span class="toc-key">{{ $s->key ?? '' }}</span>
                    <span class="toc-num">{{ $loop->index + 1 }}.</span>
                    <span class="toc-song">{{ $s->title }}</span>
                    <span class="toc-artist">{{ $s->artist->name }}</span>
                </div>
                @endforeach
            </td>
            <td class="toc-col toc-col-right">
                @foreach($col2 as $entry)
                @php $s = $entry['song']; @endphp
                <div class="toc-item">
                    <span class="toc-key">{{ $s->key ?? '' }}</span>
                    <span class="toc-num">{{ $half + $loop->index + 1 }}.</span>
                    <span class="toc-song">{{ $s->title }}</span>
                    <span class="toc-artist">{{ $s->artist->name }}</span>
                </div>
                @endforeach
            </td>
        </tr>
    </table>

</div>

{{-- ── Páginas das cifras ───────────────────────────────── --}}
@foreach($songs as $i => $entry)
@php $song = $entry['song']; $html = $entry['html']; $diagrams = $entry['diagrams']; @endphp
<div class="song-page">

    {{-- Header --}}
    <table class="song-header-table">
        <tr>
            <td class="song-header-main">
                <div class="song-num">{{ $i + 1 }} / {{ $songs->count() }}</div>
                <div class="song-title">{{ $song->title }}</div>
                <div class="song-artist">{{ $song->artist->name }}</div>
                <div class="song-meta">
                    @php
                        $meta = [];
                        if ($song->album) $meta[] = $song->album;
                        if ($song->year)  $meta[] = (string) $song->year;
                        if ($song->bpm)   $meta[] = $song->bpm . ' BPM';
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

    {{-- Conteúdo --}}
    <div class="cp-content">
        {!! $html !!}
    </div>

    {{-- Diagramas --}}
    @if(!empty($diagrams))
    <div class="diagrams-section">
        <div class="diagrams-title">Diagramas de acordes</div>
        @foreach($diagrams as $svg)
        {!! $svg !!}
        @endforeach
    </div>
    @endif

    <div class="pdf-watermark">
        querosene.test &mdash; {{ $setlist->name }}
    </div>

</div>
@endforeach

</body>
</html>
