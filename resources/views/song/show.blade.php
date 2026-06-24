@extends(request()->boolean('embed') ? 'layouts.embed' : 'layouts.app')

@section('title', $song->title . ' — ' . $song->artist->name)
@section('description', __('ui.song.meta_description', ['title' => $song->title, 'artist' => $song->artist->name]))

@push('head')
<meta property="og:type" content="music.song">
@endpush

@php
use App\Services\Import\ChordDictionary;
$chordDict = collect(ChordDictionary::all())->mapWithKeys(fn($v, $k) => [
    $k => ['name' => $k, 'strings_pattern' => $v['pattern'], 'barre' => $v['barre'], 'fingering' => null, 'fingers' => null]
]);
@endphp

@section('content')
<div
    x-data="songPlayer({
        originalKey: '{{ addslashes($song->key ?? '') }}',
        songSlug: '{{ $song->slug }}'
    })"
    x-init="init()"
    @close-video.window="videoOpen = false"
>

    {{-- ── Meta da música ───────────────────────────────────────────────── --}}
    <div id="song-meta-bar" class="bg-surface border-b border-white/5 px-4 py-5">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-black leading-tight">{{ $song->title }}</h1>
                    <p class="mt-1 text-muted">
                        <a href="{{ route('artists.show', $song->artist) }}" class="hover:text-primary transition-colors">
                            {{ $song->artist->name }}
                        </a>
                        @if($song->album)
                        <span class="mx-1.5 opacity-30">·</span>
                        <span>{{ $song->album }}</span>
                        @endif
                        @if($song->year)
                        <span class="mx-1.5 opacity-30">·</span>
                        <span>{{ $song->year }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    {{-- Salvar na Setlist --}}
                    @unless(request()->boolean('embed'))
                    @auth
                    @php $userSetlists = auth()->user()->setlists()->orderBy('name')->get(); @endphp
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @keydown.escape="open = false"
                                class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold
                                       bg-primary/10 text-primary hover:bg-primary/20 transition-colors border border-primary/20">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            {{ __('ui.setlist.save_btn') }}
                        </button>
                        <div x-show="open" @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="absolute left-0 top-full mt-1 w-52 bg-surface border border-white/10 rounded-xl shadow-xl z-30 overflow-hidden">
                            @forelse($userSetlists as $setlist)
                            <button @click="toggleSetlist({{ $setlist->id }}, $el); open = false"
                                    data-setlist="{{ $setlist->id }}"
                                    data-song="{{ $song->id }}"
                                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-white/5 transition-colors flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                {{ $setlist->name }}
                            </button>
                            @empty
                            <a href="{{ route('setlists.index') }}" @click="open = false"
                               class="block px-4 py-2.5 text-sm text-muted hover:bg-white/5 transition-colors">
                                {{ __('ui.setlist.create_first') }}
                            </a>
                            @endforelse
                        </div>
                    </div>
                    @else
                    <a href="{{ route('login') }}"
                       class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold
                              bg-white/5 text-muted hover:bg-white/10 transition-colors border border-white/10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                        {{ __('ui.setlist.save_btn') }}
                    </a>
                    @endauth
                    @endunless

                    @if($song->category)
                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold"
                          style="background:{{ $song->category->color }}22;color:{{ $song->category->color }}">
                        {{ $song->category->name }}
                    </span>
                    @endif
                    @php
                        $diffColors = ['iniciante'=>'#22c55e','intermediário'=>'#f59e0b','avançado'=>'#ef4444'];
                        $diffKeys   = ['iniciante'=>'beginner','intermediário'=>'intermediate','avançado'=>'advanced'];
                    @endphp
                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold"
                          style="background:{{ ($diffColors[$song->difficulty]??'#888') }}22;color:{{ $diffColors[$song->difficulty]??'#888' }}">
                        {{ __('ui.difficulty.' . ($diffKeys[$song->difficulty] ?? 'beginner')) }}
                    </span>
                    @if($song->bpm)
                    <span class="text-xs text-muted bg-white/5 rounded-lg px-2.5 py-1">{{ $song->bpm }} BPM</span>
                    @endif
                </div>
            </div>

            {{-- Versões disponíveis --}}
            @if($song->chords->count() > 1)
            <div class="flex items-center gap-2 mt-3">
                <span class="text-xs text-muted">{{ __('ui.song.version_label') }}</span>
                @foreach($song->chords as $chord)
                <a href="{{ route('songs.show', $song) }}?versao={{ $chord->id }}"
                   class="text-xs rounded px-2 py-1 border transition-colors
                       {{ $chord->is_default ? 'border-primary text-primary' : 'border-white/10 text-muted hover:border-white/30' }}">
                    {{ $chord->version_label }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ── Barra de controles (sticky) ─────────────────────────────────── --}}
    <div class="sticky {{ request()->boolean('embed') ? 'top-0' : 'top-16' }} z-40 bg-[#0D0D0D]/95 backdrop-blur border-b border-white/5 px-4 py-2">
        <div class="max-w-3xl mx-auto flex items-center flex-wrap gap-x-5 gap-y-2">

            {{-- Transposição --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted uppercase tracking-wider">{{ __('ui.song.key_label') }}</span>
                <button @click="transpose(-1)" class="w-7 h-7 flex items-center justify-center rounded-lg bg-surface hover:bg-white/10 text-[#F5F5F5] transition-colors text-base font-bold">−</button>
                <span class="w-10 text-center font-mono font-bold text-primary text-sm" x-text="displayKey"></span>
                <button @click="transpose(+1)" class="w-7 h-7 flex items-center justify-center rounded-lg bg-surface hover:bg-white/10 text-[#F5F5F5] transition-colors text-base font-bold">+</button>
                <button x-show="semitones !== 0" @click="resetTranspose()" class="text-xs text-muted hover:text-primary transition-colors ml-1">{{ __('ui.song.reset') }}</button>
            </div>

            {{-- Tamanho da fonte --}}
            <div class="flex items-center gap-1.5">
                <button @click="fontSize = Math.max(0, fontSize - 1)"
                        class="text-xs font-bold w-7 h-7 flex items-center justify-center rounded-lg bg-surface hover:bg-white/10 text-muted transition-colors">A−</button>
                <button @click="fontSize = Math.min(3, fontSize + 1)"
                        class="text-sm font-bold w-7 h-7 flex items-center justify-center rounded-lg bg-surface hover:bg-white/10 text-[#F5F5F5] transition-colors">A+</button>
            </div>

            {{-- Auto-scroll --}}
            <div class="flex items-center gap-2">
                <button @click="toggleScroll()"
                        :class="scrolling ? 'text-primary' : 'text-muted'"
                        class="w-7 h-7 flex items-center justify-center rounded-lg bg-surface hover:bg-white/10 transition-colors"
                        :title="scrolling ? '{{ __('ui.song.pause') }}' : '{{ __('ui.song.autoscroll') }}'">
                    <svg x-show="!scrolling" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    <svg x-show="scrolling" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                </button>
                <input
                    type="range" min="0" max="10" x-model.number="scrollSpeed"
                    @change="scrolling && restartScroll()"
                    class="w-20 accent-primary h-1 cursor-pointer"
                    title="{{ __('ui.song.scroll_speed') }}"
                >
                <span class="text-xs text-muted w-4 text-right" x-text="scrollSpeed"></span>
            </div>

            {{-- Vídeo + Foco (agrupados à direita) --}}
            <div class="ml-auto flex items-center gap-2">
                @if($youtubeId ?? null)
                <button @click="videoOpen ? closeVideo() : openVideo()"
                        :class="videoOpen ? 'text-primary bg-primary/10' : 'text-muted bg-surface'"
                        class="flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg hover:bg-white/10 transition-colors"
                        title="{{ __('ui.song.video_title') }}">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/></svg>
                    {{ __('ui.song.video') }}
                </button>
                @endif

                @unless(request()->boolean('embed'))
                <a href="{{ route('songs.pdf', $song) }}" target="_blank" rel="noopener"
                   class="flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg bg-surface text-muted hover:bg-white/10 transition-colors"
                   title="{{ __('ui.song.pdf_title') }}">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    {{ __('ui.song.pdf') }}
                </a>
                <button @click="toggleFocus()"
                        :class="focusMode ? 'text-primary bg-primary/10' : 'text-muted bg-surface'"
                        class="flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h7v2H5v5H3V3zm11 0h7v7h-2V5h-5V3zM3 14h2v5h5v2H3v-7zm16 5h-5v2h7v-7h-2v5z"/></svg>
                    {{ __('ui.song.focus') }}
                </button>
                @endunless
            </div>
        </div>
    </div>

    {{-- ── Conteúdo da cifra ───────────────────────────────────────────── --}}
    <div class="max-w-3xl mx-auto px-4 py-8">
        @if($html)
        <div id="chord-content" :class="fontSizeClasses[fontSize]">
            <div class="cp-content">
                {!! $html !!}
            </div>
        </div>
        @else
        <p class="text-muted">{{ __('ui.song.not_available') }}</p>
        @endif

        {{-- Diagrama popup --}}
        <div
            x-show="activeDiagram !== null"
            @click.outside="activeDiagram = null"
            x-transition
            class="fixed z-50 pointer-events-auto"
            :style="'left:' + diagramX + 'px; top:' + diagramY + 'px'"
        >
            <div class="bg-surface border border-white/15 rounded-xl shadow-2xl p-3 min-w-[9rem]" @click.stop>
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-primary text-sm" x-text="activeDiagram && activeDiagram.name"></span>
                    <button @click="activeDiagram = null" class="text-muted hover:text-[#F5F5F5] ml-3 w-5 h-5 flex items-center justify-center rounded hover:bg-white/10 transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div x-html="renderDiagram(activeDiagram)"></div>
            </div>
        </div>

    </div>

    {{-- ── Player YouTube flutuante ─────────────────────────────────────── --}}
    {{-- ── Sugestões ────────────────────────────────────────────────────── --}}
    @if($suggestions->isNotEmpty() && !request()->boolean('embed'))
    <div class="border-t border-white/5 bg-surface/50 py-10 px-4">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-base font-black mb-4 text-muted uppercase tracking-wider text-xs">{{ __('ui.song.you_might_like') }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($suggestions as $s)
                @include('partials.song-card', ['song' => $s])
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>

{{-- ── Player YouTube — componente isolado fora do x-data do player ── --}}
@if($youtubeId ?? null)
<div
    x-data="{
        open: false,
        src: 'about:blank',
        px: 0, py: 0, w: 320,
        ratio: ({{ $youtubeRatio }}),
        busy: false,
        startDrag(e) {
            e.preventDefault();
            this.busy = true;
            const p = e.touches ? e.touches[0] : e;
            const ox = p.clientX - this.px, oy = p.clientY - this.py;
            const onMove = ev => {
                const t = ev.touches ? ev.touches[0] : ev;
                this.px = Math.max(0, Math.min(window.innerWidth  - this.w, t.clientX - ox));
                this.py = Math.max(0, Math.min(window.innerHeight -  48,    t.clientY - oy));
            };
            const onUp = () => {
                this.busy = false;
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup',   onUp);
                window.removeEventListener('touchmove', onMove);
                window.removeEventListener('touchend',  onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup',   onUp);
            window.addEventListener('touchmove', onMove, { passive: false });
            window.addEventListener('touchend',  onUp);
        },
        startResize(e) {
            e.preventDefault();
            this.busy = true;
            const startW = this.w, startX = e.clientX;
            const onMove = ev => {
                this.w = Math.max(240, Math.min(640, startW + (ev.clientX - startX)));
                this.px = Math.min(this.px, window.innerWidth - this.w);
            };
            const onUp = () => {
                this.busy = false;
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup',   onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup',   onUp);
        },
        startResizeLeft(e) {
            e.preventDefault();
            this.busy = true;
            const startW = this.w, startX = e.clientX;
            const rightEdge  = this.px + this.w;
            const bottomEdge = this.py + Math.round(this.w / this.ratio) + 36;
            const onMove = ev => {
                const newW  = Math.max(240, Math.min(640, startW - (ev.clientX - startX)));
                const newH  = Math.round(newW / this.ratio) + 36;
                this.w  = newW;
                this.px = Math.max(0, rightEdge  - newW);
                this.py = Math.max(0, bottomEdge - newH);
            };
            const onUp = () => {
                this.busy = false;
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup',   onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup',   onUp);
        }
    }"
    @open-video.window="w = 320; px = window.innerWidth - 340; py = window.innerHeight - Math.round(320 / ({{ $youtubeRatio }}) + 36 + 20); src = $event.detail.src; open = true"
    @close-video.window="open = false; $nextTick(() => src = 'about:blank')"
    :style="`position:fixed; left:${px}px; top:${py}px; z-index:9999; width:${w}px; pointer-events:none`"
>
    <template x-if="open">
        <div style="position:relative; pointer-events:auto">
            {{-- Conteúdo do modal --}}
            <div style="border-radius:12px; overflow:hidden;
                        box-shadow:0 25px 50px -5px rgba(0,0,0,.8);
                        border:1px solid rgba(255,255,255,.1); background:#0d0d0d">
                {{-- Barra de título (área de drag) --}}
                <div
                    @mousedown="startDrag($event)"
                    @touchstart.prevent="startDrag($event)"
                    style="cursor:grab; display:flex; align-items:center; justify-content:space-between;
                           background:#111; padding:6px 12px; gap:8px; user-select:none"
                >
                    <span style="font-size:11px; color:#888; white-space:nowrap; overflow:hidden;
                                 text-overflow:ellipsis; flex:1; min-width:0">
                        <svg style="display:inline; width:12px; height:12px; margin-right:4px; vertical-align:middle" viewBox="0 0 24 24" fill="#ef4444"><path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/></svg>
                        {{ $song->title }} — {{ $song->artist->name }}
                    </span>
                    <button
                        @click.stop="$dispatch('close-video')"
                        @mousedown.stop
                        style="flex-shrink:0; width:20px; height:20px; display:flex; align-items:center;
                               justify-content:center; border-radius:4px; border:none;
                               cursor:pointer; background:transparent; color:#888"
                        onmouseover="this.style.background='rgba(255,255,255,.1)';this.style.color='#fff'"
                        onmouseout="this.style.background='transparent';this.style.color='#888'"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                {{-- Vídeo --}}
                <div style="aspect-ratio:{{ $youtubeRatio }}; background:#000; position:relative">
                    <iframe
                        :src="src"
                        width="100%" height="100%"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        style="display:block; border:0"
                    ></iframe>
                    {{-- Overlay: bloqueia o iframe durante drag/resize para o mouseup não sumir --}}
                    <div x-show="busy" style="position:absolute; inset:0; z-index:1"></div>
                </div>
            </div>
            {{-- Alça superior-esquerda (NW) --}}
            <div
                @mousedown.stop="startResizeLeft($event)"
                style="position:absolute; top:0; left:0; width:20px; height:20px;
                       cursor:nw-resize; display:flex; align-items:flex-start;
                       justify-content:flex-start; padding:4px; border-radius:12px 0 0 0"
            >
                <svg width="10" height="10" viewBox="0 0 10 10" style="display:block; transform:rotate(180deg)">
                    <line x1="2" y1="10" x2="10" y2="2" stroke="rgba(255,255,255,.35)" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="5" y1="10" x2="10" y2="5" stroke="rgba(255,255,255,.35)" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="8" y1="10" x2="10" y2="8" stroke="rgba(255,255,255,.35)" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
            {{-- Alça inferior-direita (SE) --}}
            <div
                @mousedown.stop="startResize($event)"
                style="position:absolute; bottom:0; right:0; width:20px; height:20px;
                       cursor:se-resize; display:flex; align-items:flex-end;
                       justify-content:flex-end; padding:4px; border-radius:0 0 12px 0"
            >
                <svg width="10" height="10" viewBox="0 0 10 10" style="display:block">
                    <line x1="2" y1="10" x2="10" y2="2" stroke="rgba(255,255,255,.35)" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="5" y1="10" x2="10" y2="5" stroke="rgba(255,255,255,.35)" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="8" y1="10" x2="10" y2="8" stroke="rgba(255,255,255,.35)" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </template>
</div>
@endif

@push('scripts')
<script>
const CHROMATIC  = ['C','C#','D','D#','E','F','F#','G','G#','A','A#','B'];
const FLAT_MAP   = {Db:'C#',Eb:'D#',Fb:'E',Gb:'F#',Ab:'G#',Bb:'A#',Cb:'B'};
const CHORD_DICT = @json($chordDict);
@if($youtubeId ?? null)
const YOUTUBE_EMBED = 'https://www.youtube.com/embed/{{ $youtubeId }}?rel=0&modestbranding=1&autoplay=1';
@endif

function normalizeNote(n) { return FLAT_MAP[n] || n; }

function transposeNote(note, n) {
    const norm = normalizeNote(note);
    const idx  = CHROMATIC.indexOf(norm);
    if (idx === -1) return note;
    return CHROMATIC[((idx + n) % 12 + 12) % 12];
}

function parseRoot(chord) {
    const m = chord.match(/^([A-G][#b]?)(.*)$/);
    return m ? { root: m[1], rest: m[2] } : null;
}

function transposeChord(chord, n) {
    if (!chord || n === 0) return chord;
    if (chord.includes('/')) {
        const [main, bass] = chord.split('/');
        const pb = parseRoot(bass.trim());
        return transposeChord(main, n) + '/' + (pb ? transposeNote(pb.root, n) + pb.rest : bass);
    }
    const p = parseRoot(chord);
    return p ? transposeNote(p.root, n) + p.rest : chord;
}

function songPlayer({ originalKey, songSlug }) {
    return {
        semitones: 0,
        displayKey: originalKey || '—',
        fontSize: +(localStorage.getItem('qs_fontSize') ?? 1),
        fontSizeClasses: ['cp-font-sm', 'cp-font-md', 'cp-font-lg', 'cp-font-xl'],
        scrollSpeed: 3,
        scrolling: false,
        focusMode: false,
        scrollTimer: null,
        videoOpen: false,
        activeDiagram: null,
        diagramX: 0,
        diagramY: 0,
        diagrams: Object.assign({}, CHORD_DICT),
        originalKey,

        init() {
            this.$watch('fontSize', val => localStorage.setItem('qs_fontSize', val));
            this.bindClicks();
            if (songSlug) this.fetchDiagrams();
        },

        transpose(delta) {
            const next = this.semitones + delta;
            if (next < -6 || next > 6) return;
            this.semitones = next;
            this.displayKey = this.originalKey
                ? transposeChord(this.originalKey, this.semitones)
                : '—';
            document.querySelectorAll('.cp-chord[data-chord]').forEach(el => {
                el.textContent = transposeChord(el.dataset.chord, this.semitones);
            });
        },

        resetTranspose() {
            this.semitones = 0;
            this.displayKey = this.originalKey || '—';
            document.querySelectorAll('.cp-chord[data-chord]').forEach(el => {
                el.textContent = el.dataset.chord;
            });
        },

        toggleScroll() {
            this.scrolling = !this.scrolling;
            this.scrolling ? this.startScroll() : this.stopScroll();
        },

        startScroll() {
            this.stopScroll();
            if (this.scrollSpeed === 0) { this.scrolling = false; return; }
            const pxPerTick = this.scrollSpeed * 0.4;
            let remainder = 0;
            this.scrollTimer = setInterval(() => {
                remainder += pxPerTick;
                const px = Math.floor(remainder);
                if (px > 0) { window.scrollBy(0, px); remainder -= px; }
            }, 50);
        },

        restartScroll() {
            if (this.scrolling) { this.stopScroll(); this.startScroll(); }
        },

        stopScroll() {
            if (this.scrollTimer) { clearInterval(this.scrollTimer); this.scrollTimer = null; }
        },

        toggleFocus() {
            this.focusMode = !this.focusMode;
            document.body.classList.toggle('focus-mode', this.focusMode);
        },

        openVideo() {
            if (typeof YOUTUBE_EMBED === 'undefined') return;
            window.dispatchEvent(new CustomEvent('open-video', { detail: { src: YOUTUBE_EMBED } }));
            this.videoOpen = true;
        },

        closeVideo() {
            window.dispatchEvent(new CustomEvent('close-video'));
            this.videoOpen = false;
        },

        async fetchDiagrams() {
            try {
                const res  = await fetch(`/api/v1/songs/${songSlug}/chord-diagrams`);
                const data = await res.json();
                const list = data.data || data;
                // Diagrams específicos da música sobrescrevem o dicionário
                list.forEach(d => { this.diagrams[d.name] = d; });
                // Não re-vincula — listeners já registrados no init()
            } catch (_) {}
        },

        bindClicks() {
            document.querySelectorAll('.cp-chord[data-chord]').forEach(el => {
                el.addEventListener('click', e => {
                    // Usa o nome transposto no momento do clique
                    const transposed = transposeChord(el.dataset.chord, this.semitones);
                    const base = transposed.match(/^([A-G][#b]?)/)?.[1];
                    const diag = this.diagrams[transposed] || this.diagrams[base];
                    if (!diag) return;
                    const rect = el.getBoundingClientRect();
                    const popupW = 160, popupH = 210;
                    const x = Math.min(Math.max(8, rect.left), window.innerWidth - popupW - 8);
                    let y = rect.bottom + 6;
                    if (y + popupH > window.innerHeight - 8) y = rect.top - popupH - 6;
                    y = Math.max(8, y);
                    this.diagramX = x;
                    this.diagramY = y;
                    // name reflete o acorde transposto no título do popup
                    this.activeDiagram = { ...diag, name: transposed };
                    e.stopPropagation();
                });
            });
        },

        renderDiagram(diag) {
            if (!diag || !diag.strings_pattern) return '<p class="text-xs text-muted">Sem diagrama</p>';
            const pattern = diag.strings_pattern.substring(0, 6);
            const strings = 6, fretCount = 4;
            const sw = 20, fh = 22, startX = 30, startY = 24;
            const W = startX + (strings - 1) * sw + startX;
            const H = startY + fretCount * fh + 10;

            // Parse absolute fret positions (x/-1 = muted, 0 = open, 1-9 = fret)
            const pos = Array.from(pattern).map(c => {
                if (c === 'x' || c === 'X') return -1;
                const n = parseInt(c, 10);
                return isNaN(n) ? -1 : n;
            });

            // Determine fret window: shift up if the chord sits above fret 4
            const fretted = pos.filter(p => p > 0);
            const minFret = fretted.length ? Math.min(...fretted) : 1;
            const maxFret = fretted.length ? Math.max(...fretted) : 0;
            const offset  = maxFret > fretCount ? minFret - 1 : 0; // rows to skip
            const showNut = offset === 0;

            let svg = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg">`;

            // Fret lines
            for (let f = 0; f <= fretCount; f++) {
                const y = startY + f * fh;
                svg += `<line x1="${startX}" y1="${y}" x2="${startX+(strings-1)*sw}" y2="${y}" stroke="#444" stroke-width="${f===0&&showNut?3:0.8}"/>`;
            }
            // String lines
            for (let s = 0; s < strings; s++) {
                const x = startX + s * sw;
                svg += `<line x1="${x}" y1="${startY}" x2="${x}" y2="${startY+fretCount*fh}" stroke="#444" stroke-width="0.8"/>`;
            }

            // Fret position label for barre / high-position chords
            if (!showNut || (diag.barre && diag.barre > 1)) {
                const labelFret = offset > 0 ? offset + 1 : diag.barre;
                svg += `<text x="${startX - 10}" y="${startY + fh * 0.72}" text-anchor="end" fill="#aaa" font-size="11" font-family="monospace">${labelFret}fr</text>`;
            }

            // Barre line
            if (diag.barre) {
                const relBarre = diag.barre - offset;
                if (relBarre >= 1 && relBarre <= fretCount) {
                    const by = startY + (relBarre - 0.5) * fh;
                    svg += `<line x1="${startX}" y1="${by}" x2="${startX+(strings-1)*sw}" y2="${by}" stroke="#FF6D00" stroke-width="9" stroke-linecap="round" opacity="0.45"/>`;
                }
            }

            // Markers: muted, open, fretted dots
            pos.forEach((p, i) => {
                const x = startX + i * sw;
                if (p === -1) {
                    svg += `<text x="${x}" y="${startY-7}" text-anchor="middle" fill="#666" font-size="10">✕</text>`;
                } else if (p === 0) {
                    svg += `<circle cx="${x}" cy="${startY-9}" r="4" fill="none" stroke="#888" stroke-width="1.5"/>`;
                } else {
                    const rel = p - offset;
                    if (rel >= 1 && rel <= fretCount) {
                        const y = startY + (rel - 0.5) * fh;
                        svg += `<circle cx="${x}" cy="${y}" r="7" fill="#FF6D00"/>`;
                    }
                }
            });

            svg += '</svg>';
            return svg;
        },
    };
}

function toggleSetlist(setlistId, btn) {
    const songId = btn.dataset.song;
    fetch(`/caderno/${setlistId}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                         ?? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]?.replace(/%3D/g, '=') ?? '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ song_id: songId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.error === 'limit') {
            alert('{{ __("ui.setlist.limit_reached") }}');
            return;
        }
        const svg = btn.querySelector('svg');
        if (svg) svg.style.color = data.added ? '#FF6D00' : '';
    });
}
</script>
@endpush
@endsection
