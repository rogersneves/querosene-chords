@extends(request()->boolean('embed') ? 'layouts.embed' : 'layouts.app')

@section('title', $song->title . ' — ' . $song->artist->name)
@section('description', __('ui.song.meta_description', ['title' => $song->title, 'artist' => $song->artist->name]))

@push('head')
<meta property="og:type" content="music.song">
<style>
/* chord grid */
.chord-grid { display:flex; flex-direction:column; gap:2rem; }
.section-header { font-size:.65rem; font-weight:700; letter-spacing:.12em;
    text-transform:uppercase; color:#FF6D00; border-bottom:1px solid #FF6D00;
    padding-bottom:.25rem; margin-bottom:.75rem; }
.section-bars { display:flex; flex-wrap:wrap; gap:.5rem; }
.bar-card { display:flex; flex-direction:column; align-items:center; justify-content:center;
    min-width:4rem; padding:.5rem .75rem; border-radius:.5rem;
    background:#1A1A1A; border:1px solid rgba(255,255,255,.07);
    cursor:pointer; transition:background .12s,border-color .12s; user-select:none; }
.bar-card:hover { background:rgba(255,109,0,.08); border-color:rgba(255,109,0,.3); }
.bar-card.is-active { background:rgba(255,109,0,.18); border-color:#FF6D00; }
.bar-card--fill { border-style:dashed; }
.bar-number { font-size:.6rem; color:rgba(255,255,255,.25); margin-bottom:.25rem; }
.bar-chords { display:flex; flex-wrap:wrap; gap:.2rem .4rem; justify-content:center; }
.bar-chord { font-size:var(--chord-font-size,1.25rem); font-weight:700;
    color:#FF6D00; cursor:pointer; }
.bar-chord:hover { color:#FFB300; }
.bar-fill-icon { font-size:.7rem; color:rgba(255,179,0,.6); margin-top:.2rem; }
.bar-card { position:relative; }
.bar-duration { position:absolute; top:3px; right:5px; font-size:.6rem;
    color:rgba(255,255,255,.3); font-family:monospace; }
.bar-card.is-active .bar-duration { color:rgba(255,255,255,.7); }
.bar-card.is-continuation { opacity:.5; border-style:dashed; }
.bar-card.is-continuation.is-active { opacity:1; border-style:solid; }
.bar-comment { width:100%; font-size:.95rem; color:rgba(245,245,245,.65); font-style:italic; padding:.15rem 0; }
/* transport */
.btn-transport { width:1.75rem; height:1.75rem; display:flex; align-items:center;
    justify-content:center; border-radius:.5rem; background:#1A1A1A;
    transition:background .15s,opacity .15s; }
.btn-transport:hover:not(:disabled) { background:rgba(255,255,255,.1); }
.btn-transport:disabled { opacity:.3; cursor:not-allowed; }
.btn-transport.is-active { background:#FF6D00; color:#fff; }
</style>
@endpush

@php
use App\Services\Import\ChordDictionary;
$chordDict = collect(ChordDictionary::all())->mapWithKeys(fn($v, $k) => [
    $k => ['name' => $k, 'strings_pattern' => $v['pattern'], 'barre' => $v['barre'], 'fingering' => null, 'fingers' => null]
]);
@endphp

@section('content')
@auth
@php $userSetlists = auth()->user()->setlists()->orderBy('name')->get(); @endphp
@endauth
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
                    @if($composer)
                    <p class="mt-0.5 text-xs text-white/40">{{ $composer }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    {{-- Salvar na Setlist --}}
                    @unless(request()->boolean('embed'))
                    @auth
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
                            <button @click="toggleSetlist({{ $setlist->id }}, $el, { semitones, font_size: fontSize, scroll_speed: scrollSpeed, beginner_mode: beginnerMode ? 1 : 0 }); open = false"
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
                <div class="flex flex-col items-start leading-none gap-0.5">
                    <span class="text-xs text-muted uppercase tracking-wider">{{ __('ui.song.key_label') }}</span>
                    <button x-show="semitones !== 0" @click="resetTranspose()"
                            style="display:none"
                            class="text-xs text-primary hover:underline transition-colors">{{ __('ui.song.reset') }}</button>
                </div>
                <button @click="transpose(-1)" class="w-7 h-7 flex items-center justify-center rounded-lg bg-surface hover:bg-white/10 text-[#F5F5F5] transition-colors text-base font-bold">−</button>
                <span class="w-10 text-center font-mono font-bold text-primary text-sm" x-text="displayKey"></span>
                <button @click="transpose(+1)" class="w-7 h-7 flex items-center justify-center rounded-lg bg-surface hover:bg-white/10 text-[#F5F5F5] transition-colors text-base font-bold">+</button>
            </div>

            {{-- Tamanho da fonte --}}
            <div class="flex items-center gap-1.5">
                <button @click="fontSize = Math.max(0, fontSize - 1)"
                        class="text-xs font-bold w-7 h-7 flex items-center justify-center rounded-lg bg-surface hover:bg-white/10 text-muted transition-colors">A−</button>
                <button @click="fontSize = Math.min(3, fontSize + 1)"
                        class="text-sm font-bold w-7 h-7 flex items-center justify-center rounded-lg bg-surface hover:bg-white/10 text-[#F5F5F5] transition-colors">A+</button>
            </div>

            <span class="text-white/20 select-none mx-0.5">|</span>

            {{-- Transporte --}}
            <div class="flex items-center gap-1.5">
                <button id="btn-play" onclick="playerPlay()" class="btn-transport text-[#F5F5F5]" title="Play">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </button>
                <button id="btn-pause" onclick="playerPause()" class="btn-transport text-[#F5F5F5]" disabled title="{{ __('ui.song.pause') }}">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                </button>
                <button id="btn-stop" onclick="playerStop()" class="btn-transport text-[#F5F5F5]" disabled title="Stop">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h12v12H6z"/></svg>
                </button>
                <button id="btn-mute" onclick="playerMute()" class="btn-transport text-[#F5F5F5]" disabled title="Mudo">🔊</button>
                <span id="drum-bpm" class="hidden text-xs font-mono text-amber-400"></span>
            </div>

            {{-- Barra de progresso --}}
            <div class="w-full h-0.5 bg-white/5 rounded overflow-hidden -mb-2 mt-0.5" style="flex-basis:100%;order:99">
                <div id="drum-progress" class="h-full bg-amber-500 transition-all duration-150" style="width:0%"></div>
            </div>

            {{-- Direita: Iniciante + Caderno + Foco + menu sanduíche (PDF · Vídeo) --}}
            <div class="ml-auto flex items-center gap-2">

                {{-- Versão Iniciante --}}
                @if($simplified)
                <button @click="toggleBeginner()"
                        :class="beginnerMode ? 'text-green-400 bg-green-400/10' : 'text-muted bg-surface'"
                        class="flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg hover:bg-white/10 transition-colors"
                        title="{{ __('ui.song.beginner_title') }}">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                    <span x-show="!beginnerMode">{{ __('ui.song.beginner_btn') }}</span>
                    <span x-show="beginnerMode" style="display:none" class="font-mono font-bold">Capo {{ $simplified['capo'] }}</span>
                </button>
                @endif

                {{-- Caderno: dropdown de setlists para auth; popover de login para guests --}}
                @auth
                <div x-data="{ cadOpen: false }" class="relative">
                    <button @click="cadOpen = !cadOpen"
                            :class="cadOpen ? 'text-primary bg-primary/10' : 'text-muted bg-surface'"
                            class="flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                        {{ __('ui.setlist.caderno_btn') }}
                    </button>
                    <div x-show="cadOpen"
                         @click.outside="cadOpen = false"
                         @keydown.escape.window="cadOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         style="display:none"
                         class="absolute right-0 top-full mt-2 w-52 bg-[#1A1A1A] border border-white/10 rounded-xl shadow-xl z-50 overflow-hidden">
                        @forelse($userSetlists as $setlist)
                        <button @click="toggleSetlist({{ $setlist->id }}, $el, { semitones, font_size: fontSize, scroll_speed: scrollSpeed, beginner_mode: beginnerMode ? 1 : 0 }); cadOpen = false"
                                data-setlist="{{ $setlist->id }}"
                                data-song="{{ $song->id }}"
                                class="w-full text-left px-4 py-2.5 text-sm hover:bg-white/5 transition-colors flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            {{ $setlist->name }}
                        </button>
                        @empty
                        <a href="{{ route('setlists.index') }}" @click="cadOpen = false"
                           class="block px-4 py-2.5 text-sm text-muted hover:bg-white/5 transition-colors">
                            {{ __('ui.setlist.create_first') }}
                        </a>
                        @endforelse
                    </div>
                </div>
                @else
                <div x-data="{ cadMsg: false }" class="relative">
                    <button @click="cadMsg = !cadMsg"
                            :class="cadMsg ? 'text-primary bg-primary/10' : 'text-muted bg-surface'"
                            class="flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                        {{ __('ui.setlist.caderno_btn') }}
                    </button>
                    <div x-show="cadMsg"
                         @click.outside="cadMsg = false"
                         @keydown.escape.window="cadMsg = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         style="display:none; width:270px"
                         class="absolute right-0 top-full mt-2 bg-[#1A1A1A] border border-white/10 rounded-xl shadow-2xl z-50 p-4">
                        <p class="text-xs text-[#F5F5F5] leading-relaxed mb-3">{{ __('ui.setlist.caderno_auth_required') }}</p>
                        @php $songUrl = route('songs.show', $song); @endphp
                        <div class="flex gap-2">
                            <a href="{{ route('login', ['redirect' => $songUrl]) }}" target="_top"
                               class="flex-1 text-center text-xs py-1.5 rounded-lg bg-primary text-white font-semibold hover:bg-primary/80 transition-colors whitespace-nowrap">
                                {{ __('ui.auth.login_btn') }}
                            </a>
                            <a href="{{ route('register', ['redirect' => $songUrl]) }}" target="_top"
                               class="flex-1 text-center text-xs py-1.5 rounded-lg bg-white/10 text-[#F5F5F5] font-medium hover:bg-white/15 transition-colors whitespace-nowrap">
                                {{ __('ui.auth.register_btn') }}
                            </a>
                        </div>
                    </div>
                </div>
                @endauth

                {{-- Foco --}}
                @unless(request()->boolean('embed'))
                <button @click="toggleFocus()"
                        :class="focusMode ? 'text-primary bg-primary/10' : 'text-muted bg-surface'"
                        class="flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h7v2H5v5H3V3zm11 0h7v7h-2V5h-5V3zM3 14h2v5h5v2H3v-7zm16 5h-5v2h7v-7h-2v5z"/></svg>
                    {{ __('ui.song.focus') }}
                </button>
                @endunless

                {{-- Menu sanduíche: PDF + Vídeo --}}
                <div x-data="{ menuOpen: false }" class="relative">
                    <button @click="menuOpen = !menuOpen"
                            :class="menuOpen ? 'text-primary bg-primary/10' : 'text-muted bg-surface'"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 transition-colors"
                            title="Menu">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>

                    <div x-show="menuOpen"
                         @click.outside="menuOpen = false"
                         @keydown.escape.window="menuOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         style="display:none; width:180px"
                         class="absolute right-0 top-full mt-2 bg-[#1A1A1A] border border-white/10 rounded-xl shadow-2xl z-50 overflow-hidden">

                        {{-- PDF --}}
                        @auth
                        <a href="{{ route('songs.pdf', $song) }}" target="_blank" rel="noopener"
                           @click="menuOpen = false"
                           class="flex items-center gap-3 px-4 py-3 text-sm text-[#F5F5F5] hover:bg-white/5 transition-colors">
                            <svg class="w-4 h-4 text-white/30 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            {{ __('ui.song.pdf') }}
                        </a>
                        @else
                        <div class="flex items-center gap-3 px-4 py-3 text-sm text-white/30">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            {{ __('ui.song.pdf') }}
                            <a href="{{ route('login') }}" target="_top" class="ml-auto text-primary hover:underline text-xs">{{ __('ui.auth.login_btn') }}</a>
                        </div>
                        @endauth

                        {{-- Vídeo --}}
                        @if($youtubeId ?? null)
                        <div class="border-t border-white/5"></div>
                        <button @click="videoOpen ? closeVideo() : openVideo(); menuOpen = false"
                                :class="videoOpen ? 'text-primary' : 'text-[#F5F5F5]'"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-white/5 transition-colors">
                            <svg class="w-4 h-4 text-white/30 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/></svg>
                            {{ __('ui.song.video') }}
                        </button>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Conteúdo da cifra ───────────────────────────────────────────── --}}
    <div class="max-w-3xl mx-auto px-4 py-8">
        @if($grid)
        @php
            $allComments = collect($grid)
                ->flatMap(fn($s) => $s['bars'])
                ->filter(fn($b) => ($b['type'] ?? 'bar') === 'comment')
                ->values();
            $lastCid = $allComments->isNotEmpty() ? $allComments->last()['cid'] : -1;
        @endphp
        @if($allComments->count() > 1)
        <div class="mb-6 flex flex-col gap-0.5">
            @foreach($allComments->slice(0, -1) as $c)
            <p class="bar-comment">{{ $c['text'] }}</p>
            @endforeach
        </div>
        @endif
        <div id="chord-grid" class="chord-grid">
            @foreach($grid as $section)
            <div class="chord-section">
                @if($section['section_label'])
                <div class="section-header">{{ $section['section_label'] }}</div>
                @endif
                <div class="section-bars">
                    @foreach($section['bars'] as $bar)
                    @if(($bar['type'] ?? 'bar') === 'comment')
                    @if($bar['cid'] === $lastCid)
                    <div class="bar-comment">{{ $bar['text'] }}</div>
                    @endif
                    @else
                    @php
                        $cardClass = 'bar-card';
                        if ($bar['is_fill'])                $cardClass .= ' bar-card--fill';
                        if (($bar['bar_offset'] ?? 0) > 0)  $cardClass .= ' is-continuation';
                    @endphp
                    <div class="{{ $cardClass }}"
                         data-bar-index="{{ $bar['index'] }}"
                         onclick="seekToBar({{ $bar['index'] }})">
                        <div class="bar-number">{{ $bar['index'] + 1 }}</div>
                        <div class="bar-chords">
                            @foreach($bar['chords'] as $chord)
                            <span class="bar-chord" data-chord="{{ $chord }}">{{ $chord }}</span>
                            @endforeach
                        </div>
                        @if(($bar['bar_offset'] ?? 0) === 0 && ($bar['bars_count'] ?? 1) > 1)
                        <span class="bar-duration">×{{ $bar['bars_count'] }}</span>
                        @endif
                        @if($bar['is_fill'])
                        <div class="bar-fill-icon">▸ fill</div>
                        @endif
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endforeach
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/tone/14.8.49/Tone.js"></script>
<script>
const CHROMATIC    = ['C','C#','D','D#','E','F','F#','G','G#','A','A#','B'];
const FLAT_MAP     = {Db:'C#',Eb:'D#',Fb:'E',Gb:'F#',Ab:'G#',Bb:'A#',Cb:'B'};
const CHORD_DICT   = @json($chordDict);
const BEGINNER_DATA = {!! json_encode($simplified, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!};
@php
$barsChords = collect($grid)
    ->flatMap(fn($s) => $s['bars'])
    ->filter(fn($b) => ($b['type'] ?? 'bar') === 'bar')
    ->mapWithKeys(fn($b) => [$b['index'] => $b['chords']])
    ->toArray();
@endphp
const BARS_CHORDS = {!! json_encode($barsChords, JSON_HEX_TAG) !!};
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
    const beginnerData = BEGINNER_DATA;
    const fontSizes = ['1.0rem', '1.25rem', '1.55rem', '1.9rem'];
    return {
        semitones: 0,
        displayKey: originalKey || '—',
        fontSize: +(localStorage.getItem('qs_fontSize') ?? 1),
        scrollSpeed: 3,
        focusMode: false,
        videoOpen: false,
        activeDiagram: null,
        diagramX: 0,
        diagramY: 0,
        diagrams: Object.assign({}, CHORD_DICT),
        originalKey,
        beginnerMode: false,

        init() {
            const params = new URLSearchParams(window.location.search);
            if (params.has('font_size')) this.fontSize = Math.min(3, Math.max(0, +params.get('font_size')));

            const initBeginner = params.get('beginner_mode') === '1' && !!beginnerData;
            const initSemitones = params.has('semitones') && !initBeginner
                ? Math.min(6, Math.max(-6, +params.get('semitones')))
                : null;

            const setFontVar = val => document.documentElement.style.setProperty(
                '--chord-font-size', fontSizes[Math.min(3, Math.max(0, val))] ?? fontSizes[1]
            );
            setFontVar(this.fontSize);
            this.$watch('fontSize', val => { localStorage.setItem('qs_fontSize', val); setFontVar(val); });

            this.bindClicks();
            if (songSlug) this.fetchDiagrams();

            this.$nextTick(() => {
                if (initBeginner) {
                    this.beginnerMode = true;
                    this.semitones    = beginnerData.semitones;
                    this.displayKey   = this.originalKey ? transposeChord(this.originalKey, this.semitones) : '—';
                    document.querySelectorAll('.bar-chord[data-chord]').forEach(el => {
                        el.textContent = transposeChord(el.dataset.chord, this.semitones);
                    });
                } else if (initSemitones !== null && initSemitones !== 0) {
                    this.semitones  = initSemitones;
                    this.displayKey = this.originalKey ? transposeChord(this.originalKey, this.semitones) : '—';
                    document.querySelectorAll('.bar-chord[data-chord]').forEach(el => {
                        el.textContent = transposeChord(el.dataset.chord, this.semitones);
                    });
                }
            });
        },

        transpose(delta) {
            const next = this.semitones + delta;
            if (next < -6 || next > 6) return;
            this.semitones = next;
            this.beginnerMode = false;
            this.displayKey = this.originalKey ? transposeChord(this.originalKey, this.semitones) : '—';
            document.querySelectorAll('.bar-chord[data-chord]').forEach(el => {
                el.textContent = transposeChord(el.dataset.chord, this.semitones);
            });
        },

        resetTranspose() {
            this.semitones = 0;
            this.beginnerMode = false;
            this.displayKey = this.originalKey || '—';
            document.querySelectorAll('.bar-chord[data-chord]').forEach(el => {
                el.textContent = el.dataset.chord;
            });
        },

        toggleBeginner() {
            if (!beginnerData) return;
            this.beginnerMode = !this.beginnerMode;
            if (this.beginnerMode) {
                this.semitones = beginnerData.semitones;
                this.displayKey = this.originalKey ? transposeChord(this.originalKey, this.semitones) : '—';
                document.querySelectorAll('.bar-chord[data-chord]').forEach(el => {
                    el.textContent = transposeChord(el.dataset.chord, this.semitones);
                });
            } else {
                this.semitones = 0;
                this.displayKey = this.originalKey || '—';
                document.querySelectorAll('.bar-chord[data-chord]').forEach(el => {
                    el.textContent = el.dataset.chord;
                });
            }
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
                list.forEach(d => { this.diagrams[d.name] = d; });
            } catch (_) {}
        },

        bindClicks() {
            document.querySelectorAll('.bar-chord[data-chord]').forEach(el => {
                el.addEventListener('click', e => {
                    e.stopPropagation();
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
                    this.activeDiagram = { ...diag, name: transposed };
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
            const showCapo = this.beginnerMode && beginnerData && beginnerData.capo > 0;

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

            // Capo indicator: green bar in the middle of fret 1 + star + fret number on the right
            if (showCapo && showNut) {
                const capoY = startY + fh * 0.5;
                svg += `<rect x="${startX-6}" y="${capoY-5}" width="${(strings-1)*sw+12}" height="10" rx="5" fill="#22c55e" opacity="0.88"/>`;
                svg += `<text x="${W-12}" y="${capoY}" text-anchor="middle" dominant-baseline="central" font-size="11" fill="#22c55e">★${beginnerData.capo}</text>`;
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
                    const capoShift = (showCapo && showNut) ? 1 : 0;
                    const rel = p - offset + capoShift;
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

// ── Transport Player ─────────────────────────────────────────────────────────
const SONG_SLUG = '{{ $song->slug }}';
const LOCALE    = '{{ app()->getLocale() }}';

const state = {
    status:     'stopped',
    muted:      false,
    currentBar: 0,
    currentStep: 0,
    totalBars:  0,
    drumLoaded: false,
    drumData:   null,
};

const drumKick = new Tone.MembraneSynth({
    pitchDecay: 0.03, octaves: 8,
    envelope: { attack: 0.001, decay: 0.18, sustain: 0, release: 0.25, attackCurve: 'exponential' },
}).toDestination();

const drumHihat = new Tone.MetalSynth({
    frequency: 400, harmonicity: 5.1, modulationIndex: 32, resonance: 4000, octaves: 1.5,
    envelope: { attack: 0.001, decay: 0.05, release: 0.01 },
}).toDestination();
drumHihat.volume.value = -12;

const chordSynth = new Tone.PolySynth(Tone.Synth, {
    oscillator: { type: 'triangle' },
    envelope: { attack: 0.02, decay: 0.3, sustain: 0.4, release: 1.2 },
    volume: -14,
}).toDestination();

const CHORD_NOTES = {
    // Tríades maiores
    'C':['C3','E3','G3','C4'],'C#':['C#3','F3','G#3','C#4'],'Db':['C#3','F3','G#3','C#4'],
    'D':['D3','F#3','A3','D4'],'D#':['D#3','G3','A#3','D#4'],'Eb':['D#3','G3','A#3','D#4'],
    'E':['E3','G#3','B3','E4'],'F':['F3','A3','C4','F4'],
    'F#':['F#3','A#3','C#4','F#4'],'Gb':['F#3','A#3','C#4','F#4'],
    'G':['G3','B3','D4','G4'],'G#':['G#3','C4','D#4','G#4'],'Ab':['G#3','C4','D#4','G#4'],
    'A':['A3','C#4','E4','A4'],'A#':['A#3','D4','F4','A#4'],'Bb':['A#3','D4','F4','A#4'],
    'B':['B3','D#4','F#4','B4'],
    // Menores
    'Am':['A3','C4','E4','A4'],'Am6':['A3','C4','E4','F#4'],'Am7':['A3','C4','E4','G4'],
    'Bm':['B3','D4','F#4','B4'],'Bm7':['B3','D4','F#4','A4'],
    'Cm':['C3','D#3','G3','C4'],'Cm7':['C3','D#3','G3','A#3'],
    'Dm':['D3','F3','A3','D4'],'Dm7':['D3','F3','A3','C4'],
    'Em':['E3','G3','B3','E4'],'Em7':['E3','G3','B3','D4'],
    'F#m':['F#3','A3','C#4','F#4'],'F#m7':['F#3','A3','C#4','E4'],
    'Gm':['G3','A#3','D4','G4'],'Gm7':['G3','A#3','D4','F4'],
    'G#m':['G#3','B3','D#4','G#4'],'Abm':['G#3','B3','D#4','G#4'],
    // Dominantes
    'A7':['A3','C#4','E4','G4'],'B7':['B3','D#4','F#4','A4'],
    'C7':['C3','E3','G3','A#3'],'D7':['D3','F#3','A3','C4'],
    'E7':['E3','G#3','B3','D4'],'F7':['F3','A3','C4','D#4'],
    'F#7':['F#3','A#3','C#4','E4'],'G7':['G3','B3','D4','F4'],
    'G#7':['G#3','C4','D#4','F#4'],'Ab7':['G#3','C4','D#4','F#4'],
    'Bb7':['A#3','D4','F4','G#4'],
    // Maj7
    'Cmaj7':['C3','E3','G3','B3'],'Dmaj7':['D3','F#3','A3','C#4'],
    'Emaj7':['E3','G#3','B3','D#4'],'Fmaj7':['F3','A3','C4','E4'],
    'Gmaj7':['G3','B3','D4','F#4'],'Amaj7':['A3','C#4','E4','G#4'],
    'Bbmaj7':['A#3','D4','F4','A4'],
    // Suspensos
    'Asus4':['A3','D4','E4','A4'],'Dsus4':['D3','G3','A3','D4'],
    'Esus4':['E3','A3','B3','E4'],'Gsus4':['G3','C4','D4','G4'],
    // Outros
    'Bb6':['A#3','D4','F4','G4'],'Gm6':['G3','A#3','D4','E4'],
};

function resolveChordNotes(name) {
    if (CHORD_NOTES[name]) return CHORD_NOTES[name];
    const suffixes = ['maj7','min7','sus4','sus2','add9','dim7','dim','aug','maj','7','6','9','m'];
    for (const s of suffixes) {
        if (name.endsWith(s)) {
            const base = name.slice(0, -s.length);
            if (CHORD_NOTES[base]) return CHORD_NOTES[base];
        }
    }
    const root = name.replace(/[^A-G#b].*/, '');
    return CHORD_NOTES[root] || null;
}

function triggerDrum(inst, time) {
    if (state.muted) return;
    if (inst === 'kick')  drumKick.triggerAttackRelease('C1', '16n', time);
    if (inst === 'hihat') drumHihat.triggerAttackRelease('32n', time);
}

let sequence = null;

async function loadDrumData() {
    const res          = await fetch(`/api/v1/songs/${SONG_SLUG}/drum-pattern`);
    state.drumData     = await res.json();
    state.totalBars    = state.drumData.bars_map.length;
    state.drumLoaded   = true;
    Tone.getTransport().bpm.value = state.drumData.bpm;
    const lbl = document.getElementById('drum-bpm');
    if (lbl) lbl.textContent = state.drumData.bpm + ' BPM';
}

function resolvePattern() {
    if (!state.drumData) return null;
    let offset = 0;
    for (const hint of state.drumData.drum_hints) {
        if (state.currentBar >= offset && state.currentBar < offset + hint.bars) {
            return state.drumData.pattern.patterns[hint.pattern]
                ?? state.drumData.pattern.patterns.main;
        }
        offset += hint.bars;
    }
    return state.drumData.pattern.patterns.main;
}

function buildSequencer() {
    sequence?.dispose();
    sequence = new Tone.Sequence((time) => {
        const pat = resolvePattern();
        if (pat) {
            ['kick', 'hihat'].forEach(inst => {
                if (pat[inst]?.includes(state.currentStep)) triggerDrum(inst, time);
            });
        }
        state.currentStep++;
        if (state.currentStep >= 16) {
            state.currentStep = 0;
            advanceBar();
        }
    }, [...Array(16).keys()], '16n');
}

function activateCard(idx) {
    const el = document.querySelector(`.bar-card[data-bar-index="${idx}"]`);
    if (!el) return;
    el.classList.add('is-active');

    // Rola quando o card entrar na metade inferior do viewport,
    // posicionando-o no topo — garante que os próximos acordes fiquem visíveis
    const rect = el.getBoundingClientRect();
    if (rect.bottom > window.innerHeight * 0.65) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function deactivateCard(idx) {
    const el = document.querySelector(`.bar-card[data-bar-index="${idx}"]`);
    if (el) el.classList.remove('is-active');
}

function clearAllCards() {
    document.querySelectorAll('.bar-card.is-active').forEach(el => el.classList.remove('is-active'));
}

function advanceBar() {
    deactivateCard(state.currentBar);
    state.currentBar++;
    if (state.currentBar >= state.totalBars) state.currentBar = 0;
    activateCard(state.currentBar);
    updateProgress();
    playBarChord(state.currentBar);
}

function playBarChord(barIndex) {
    if (state.muted) return;
    const chordNames = BARS_CHORDS[barIndex];
    if (!chordNames?.length) return;
    const barDuration = (60 / state.drumData.bpm) * 4;

    const scheduleChord = (name, duration, offset) => {
        const notes = resolveChordNotes(name);
        if (!notes) return;
        if (offset) {
            Tone.Transport.scheduleOnce((time) => {
                if (state.status === 'playing' && !state.muted)
                    chordSynth.triggerAttackRelease(notes, duration, time);
            }, `+${offset}`);
        } else {
            chordSynth.triggerAttackRelease(notes, duration);
        }
    };

    if (chordNames.length >= 2) {
        const half = barDuration / 2;
        scheduleChord(chordNames[0], half * 0.85, 0);
        scheduleChord(chordNames[1], half * 0.85, half);
    } else {
        scheduleChord(chordNames[0], barDuration * 0.85, 0);
    }
}

function seekToBar(idx) {
    deactivateCard(state.currentBar);
    state.currentBar  = idx;
    state.currentStep = 0;
    activateCard(state.currentBar);
    updateProgress();
}

function updateProgress() {
    const pct = state.totalBars > 0 ? (state.currentBar / state.totalBars) * 100 : 0;
    const bar = document.getElementById('drum-progress');
    if (bar) bar.style.width = pct + '%';
}

function updateButtons() {
    const playing = state.status === 'playing';
    const stopped = state.status === 'stopped';
    const btnPlay  = document.getElementById('btn-play');
    const btnPause = document.getElementById('btn-pause');
    const btnStop  = document.getElementById('btn-stop');
    const btnMute  = document.getElementById('btn-mute');
    const bpmLabel = document.getElementById('drum-bpm');
    btnPlay.disabled  = playing;
    btnPlay.classList.toggle('is-active', playing);
    btnPause.disabled = !playing;
    btnStop.disabled  = stopped;
    btnMute.disabled  = stopped;
    btnMute.textContent = state.muted ? '🔇' : '🔊';
    btnMute.classList.toggle('is-active', state.muted);
    if (bpmLabel) bpmLabel.classList.toggle('hidden', stopped);
}

async function playerPlay() {
    const btnPlay = document.getElementById('btn-play');
    if (!state.drumLoaded) {
        btnPlay.disabled = true;
        await loadDrumData();
        btnPlay.disabled = false;
    }
    await Tone.start();
    if (state.status === 'paused') {
        Tone.getTransport().start();
    } else {
        buildSequencer();
        activateCard(state.currentBar);
        playBarChord(state.currentBar);
        sequence.start(0);
        Tone.getTransport().start();
    }
    state.status = 'playing';
    updateButtons();
}

function playerPause() {
    if (state.status !== 'playing') return;
    Tone.getTransport().pause();
    state.status = 'paused';
    updateButtons();
}

function playerStop() {
    if (state.status === 'stopped') return;
    sequence?.stop();
    Tone.getTransport().stop();
    chordSynth.releaseAll();
    clearAllCards();
    state.status      = 'stopped';
    state.currentBar  = 0;
    state.currentStep = 0;
    state.muted       = false;
    chordSynth.volume.value = -14;
    updateProgress();
    updateButtons();
}

function playerMute() {
    state.muted = !state.muted;
    chordSynth.volume.value = state.muted ? -Infinity : -14;
    updateButtons();
}

function toggleSetlist(setlistId, btn, settings = {}) {
    const songId = btn.dataset.song;
    fetch(`/caderno/${setlistId}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                         ?? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]?.replace(/%3D/g, '=') ?? '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ song_id: songId, ...settings }),
    })
    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(data => {
        if (data.error === 'limit') {
            alert('{{ __("ui.setlist.limit_reached") }}');
            return;
        }
        const svg = btn.querySelector('svg');
        if (svg) svg.style.color = (data.added || data.updated) ? '#FF6D00' : '';
        const msg  = data.added   ? '{{ __("ui.setlist.song_added") }}'
                   : data.updated ? '{{ __("ui.setlist.song_updated") }}'
                   :                '{{ __("ui.setlist.song_removed") }}';
        const type = data.updated ? 'updated' : (data.added ? 'success' : 'removed');
        const fire = (win) => win.dispatchEvent(new CustomEvent('show-toast', { detail: { msg, type } }));
        fire(window);
        if (window.parent !== window) try { fire(window.parent); } catch(e) {}
    })
    .catch(() => {});
}
</script>
@endpush
@endsection
