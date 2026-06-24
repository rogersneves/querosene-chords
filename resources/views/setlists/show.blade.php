@extends('layouts.app')
@section('title', $setlist->name . ' — ' . __('ui.setlist.my_title'))

@section('content')
<div x-data="{
    openSong(url, title) {
        window.dispatchEvent(new CustomEvent('open-song-modal', { detail: { url, title } }));
    }
}">

<div class="max-w-3xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('setlists.index') }}" class="text-xs text-muted hover:text-primary transition-colors">
                ← {{ __('ui.setlist.back') }}
            </a>
            <h1 class="text-2xl font-black mt-1">{{ $setlist->name }}</h1>
            <p class="text-xs text-muted mt-0.5">
                {{ trans_choice('ui.setlist.songs_count', $setlist->songs->count(), ['count' => $setlist->songs->count()]) }}
            </p>
        </div>

        {{-- Ações (PDF + Rename) --}}
        <div class="flex items-start gap-2 shrink-0">

        {{-- Exportar PDF --}}
        @if($setlist->songs->isNotEmpty())
        <a href="{{ route('setlists.pdf', $setlist) }}" target="_blank" rel="noopener"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs bg-white/5 hover:bg-white/10 transition-colors text-muted"
           title="{{ __('ui.setlist.pdf_title') }}">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            {{ __('ui.setlist.pdf_btn') }}
        </a>
        @endif

        {{-- Rename --}}
        <div x-data="{ editing: false }" class="shrink-0">
            <button @click="editing = !editing"
                    class="px-3 py-1.5 rounded-lg text-xs bg-white/5 hover:bg-white/10 transition-colors">
                {{ __('ui.setlist.rename') }}
            </button>
            <form x-show="editing" method="POST" action="{{ route('setlists.rename', $setlist) }}"
                  class="flex gap-2 mt-2" @click.outside="editing = false">
                @csrf @method('PATCH')
                <input type="text" name="name" value="{{ $setlist->name }}" required
                       class="bg-surface border border-white/10 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-primary">
                <button type="submit"
                        class="px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-bold">OK</button>
            </form>
        </div>

        </div>{{-- /ações --}}
    </div>

    @if(session('success'))
    <div class="bg-green-500/10 border border-green-500/20 text-green-400 rounded-xl px-4 py-3 text-sm mb-6">
        {{ session('success') }}
    </div>
    @endif

    {{-- Song list --}}
    @php
        $diffColors = ['iniciante'=>'#22c55e','intermediário'=>'#f59e0b','avançado'=>'#ef4444'];
        $diffKeys   = ['iniciante'=>'beginner','intermediário'=>'intermediate','avançado'=>'advanced'];
    @endphp
    @forelse($setlist->songs as $song)
    <div class="bg-surface rounded-xl border border-white/5 px-4 py-3 mb-2 flex items-center gap-3">
        {{-- Título + artista --}}
        <div class="min-w-0 flex-1">
            <button
                @click="openSong('{{ route('songs.show', $song) }}', '{{ addslashes($song->title) }}')"
                class="font-semibold text-sm hover:text-primary transition-colors truncate block text-left w-full">
                {{ $song->title }}
            </button>
            <p class="text-xs text-muted truncate">{{ $song->artist->name }}</p>
        </div>
        {{-- Tom --}}
        <div class="hidden sm:block w-10 shrink-0">
            @if($song->key)
            <span class="text-xs font-mono text-primary">{{ $song->key }}</span>
            @endif
        </div>
        {{-- Badges --}}
        <div class="hidden sm:flex items-center gap-1.5 w-52 shrink-0">
            @if($song->category)
            <span class="text-xs rounded px-2 py-0.5 font-medium"
                  style="background:{{ $song->category->color }}22;color:{{ $song->category->color }}">
                {{ $song->category->name }}
            </span>
            @endif
            @if($song->difficulty && isset($diffColors[$song->difficulty]))
            <span class="text-xs rounded px-2 py-0.5 font-medium"
                  style="background:{{ $diffColors[$song->difficulty] }}22;color:{{ $diffColors[$song->difficulty] }}">
                {{ __('ui.difficulty.' . $diffKeys[$song->difficulty]) }}
            </span>
            @endif
        </div>
        {{-- Remover --}}
        <form method="POST" action="{{ route('setlists.remove-song', [$setlist, $song]) }}">
            @csrf @method('DELETE')
            <button type="submit" title="{{ __('ui.setlist.remove_song') }}"
                    class="text-muted hover:text-red-400 transition-colors p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </form>
    </div>
    @empty
    <div class="text-center py-12 text-muted">
        <p class="text-4xl mb-3">🎵</p>
        <p>{{ __('ui.setlist.no_songs') }}</p>
        <a href="{{ route('home') }}" class="text-primary text-sm hover:text-secondary mt-2 inline-block transition-colors">
            {{ __('ui.setlist.browse_songs') }}
        </a>
    </div>
    @endforelse

</div>

</div>
@endsection
