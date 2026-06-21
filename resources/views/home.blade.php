@extends('layouts.app')

@section('title', 'Querosene Chords')

@section('content')
<div>

    {{-- ── Hero ──────────────────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden py-20 px-4 text-center bg-gradient-to-b from-[#1A1A1A] to-canvas">
        <div class="absolute inset-0 opacity-5 bg-[radial-gradient(ellipse_80%_60%_at_50%_0%,#FF6D00,transparent)]"></div>
        <div class="relative max-w-2xl mx-auto">
            <h1 class="text-4xl md:text-6xl font-black leading-tight mb-3">
                Dê um <span class="text-primary">gás</span> na sua música
            </h1>
            <p class="text-muted mb-8 text-lg">
                {{ number_format($total, 0, ',', '.') }} cifras disponíveis, de graça e no capricho.
            </p>

            {{-- Search no hero --}}
            <a href="{{ route('search') }}"
               class="flex items-center gap-3 max-w-lg mx-auto bg-surface border border-white/10 rounded-2xl px-5 py-3.5 hover:border-primary/50 transition-colors group">
                <svg class="w-5 h-5 text-muted group-hover:text-primary transition-colors shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l4.387 4.387a1 1 0 01-1.414 1.414l-4.387-4.387zM8 14A6 6 0 108 2a6 6 0 000 12z"/>
                </svg>
                <span class="text-muted text-base group-hover:text-[#F5F5F5] transition-colors">Buscar cifras e artistas…</span>
            </a>
        </div>
    </section>

    {{-- ── Categorias ───────────────────────────────────────────────────── --}}
    @if($categories->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 py-12">
        <h2 class="text-xl font-black mb-6">Categorias</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach($categories as $cat)
            <a href="{{ route('categories.show', $cat) }}"
               class="flex items-center gap-3 p-4 rounded-xl border border-white/5 hover:border-white/10 transition-all group"
               style="background: {{ $cat->color }}12;">
                <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $cat->color }}"></span>
                <span class="font-bold text-sm text-[#F5F5F5] group-hover:text-primary transition-colors truncate">{{ $cat->name }}</span>
                @if($cat->songs_count)
                <span class="ml-auto text-xs text-muted shrink-0">{{ $cat->songs_count }}</span>
                @endif
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Populares ────────────────────────────────────────────────────── --}}
    @if($popular->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 pb-12">
        <h2 class="text-xl font-black mb-6">🔥 Mais tocadas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($popular as $song)
            @include('partials.song-card', ['song' => $song])
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Novidades ────────────────────────────────────────────────────── --}}
    @if($recent->isNotEmpty())
    <section class="pb-16">
        <div class="max-w-7xl mx-auto px-4 mb-4">
            <h2 class="text-xl font-black">Novidades</h2>
        </div>
        <div class="overflow-x-auto pb-4">
            <div class="flex gap-3 px-4 max-w-7xl mx-auto" style="width: max-content;">
                @foreach($recent as $song)
                <a href="{{ route('songs.show', $song) }}"
                   class="w-44 shrink-0 bg-surface rounded-xl p-3 border border-white/5 hover:border-white/10 hover:bg-white/5 transition-all group">
                    <p class="text-sm font-bold text-[#F5F5F5] group-hover:text-primary transition-colors leading-tight truncate">{{ $song->title }}</p>
                    <p class="text-xs text-muted mt-1 truncate">{{ $song->artist->name }}</p>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

</div>
@endsection
