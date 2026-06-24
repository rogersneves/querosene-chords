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
}

/* ── Header ──────────────────────────────────────────── */
.song-header {
    border-bottom: 2pt solid #FF6D00;
    padding-bottom: 8pt;
    margin-bottom: 14pt;
}
.song-title  { font-size: 17pt; font-weight: bold; margin-bottom: 3pt; }
.song-artist { font-size: 11pt; color: #444; margin-bottom: 4pt; }
.song-meta   { font-size: 8pt; color: #888; }
.song-meta span { margin-right: 12pt; }
.song-meta .key-badge {
    display: inline-block;
    background: #fff2eb;
    color: #e65c00;
    border: 1pt solid #FF6D00;
    border-radius: 3pt;
    padding: 1pt 5pt;
    font-weight: bold;
    font-size: 8pt;
}

/* ── ChordPro content ────────────────────────────────── */
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
    font-family: "Courier New", "Courier", monospace;
    font-size: 7.5pt;
    line-height: 1.4;
    white-space: pre;
}

.cp-comment {
    font-style: italic;
    color: #777;
    font-size: 9pt;
    margin: 3pt 0;
}

.cp-annotation {
    display: block;
    font-size: 9pt;
    margin-bottom: 2pt;
}
.cp-annotation-label  { font-weight: bold; color: #555; margin-right: 4pt; }
.cp-annotation .cp-chord { display: inline; font-size: 9pt; font-weight: bold; color: #e65c00; margin: 0 2pt; }
.cp-annotation-suffix { color: #777; margin-left: 2pt; }

/* ── Chord diagrams ───────────────────────────────────── */
.diagrams-section {
    border-top: 1pt solid #ddd;
    padding-top: 10pt;
    margin-top: 10pt;
}
.diagrams-title {
    font-size: 7.5pt;
    font-weight: bold;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    margin-bottom: 6pt;
}
.diagrams-row { display: block; }
.diagram-item {
    display: inline-block;
    vertical-align: top;
    margin-right: 6pt;
    margin-bottom: 4pt;
    text-align: center;
}

/* ── Watermark footer ─────────────────────────────────── */
.pdf-watermark {
    margin-top: 14pt;
    padding-top: 6pt;
    border-top: 0.5pt solid #eee;
    text-align: center;
    font-size: 7pt;
    color: #ccc;
}
</style>
</head>
<body>

<div class="song-header">
    <div class="song-title">{{ $song->title }}</div>
    <div class="song-artist">{{ $song->artist->name }}</div>
    <div class="song-meta">
        @if($song->key)
        <span class="key-badge">{{ $song->key }}</span>
        @endif
        @if($song->album)
        <span>{{ $song->album }}</span>
        @endif
        @if($song->year)
        <span>{{ $song->year }}</span>
        @endif
        @if($song->difficulty)
        @php $diffs = ['iniciante'=>'Iniciante','intermediário'=>'Intermediário','avançado'=>'Avançado']; @endphp
        <span>{{ $diffs[$song->difficulty] ?? ucfirst($song->difficulty) }}</span>
        @endif
        @if($song->bpm)
        <span>{{ $song->bpm }} BPM</span>
        @endif
    </div>
</div>

<div class="cp-content">
    {!! $html !!}
</div>

@if(!empty($diagrams))
<div class="diagrams-section">
    <div class="diagrams-title">Diagramas de acordes</div>
    <div class="diagrams-row">
        @foreach($diagrams as $name => $svg)
        <div class="diagram-item">
            {!! $svg !!}
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="pdf-watermark">
    querosene.test &mdash; Dê um gás na sua música
</div>

</body>
</html>
