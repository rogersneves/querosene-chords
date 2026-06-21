@extends('layouts.app')

@section('title', $artist->name)
@section('description', __('ui.artist.meta_description', [
    'name' => $artist->name,
    'bio'  => $artist->bio ? strip_tags(substr($artist->bio, 0, 120)) . '…' : __('ui.artist.bio_fallback'),
]))

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Cabeçalho do artista --}}
    <div class="flex items-start gap-6 mb-10">
        <div class="w-20 h-20 md:w-28 md:h-28 rounded-2xl overflow-hidden bg-primary/20 flex items-center justify-center text-primary font-black text-4xl shrink-0">
            @if($artist->photo_path)
            <img src="{{ Storage::disk('public')->url($artist->photo_path) }}" alt="{{ $artist->name }}" class="w-full h-full object-cover">
            @else
            {{ mb_substr($artist->name, 0, 1) }}
            @endif
        </div>
        <div class="min-w-0">
            <h1 class="text-3xl md:text-4xl font-black leading-tight">{{ $artist->name }}</h1>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm text-muted">
                @if($artist->genre)
                <span>{{ $artist->genre }}</span>
                @endif
                @if($artist->country)
                <span>{{ $artist->country }}</span>
                @endif
                <span>{{ trans_choice('ui.artist.count', $songs->total(), ['count' => $songs->total()]) }}</span>
            </div>
            @if($artist->bio)
            <p class="mt-3 text-sm text-muted max-w-2xl leading-relaxed line-clamp-4">{{ strip_tags($artist->bio) }}</p>
            @endif
        </div>
    </div>

    {{-- Grid de músicas --}}
    <h2 class="text-lg font-black mb-4">{{ __('ui.artist.all_chords') }}</h2>
    @if($songs->isEmpty())
    <p class="text-muted">{{ __('ui.artist.no_chords') }}</p>
    @else
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-6">
        @foreach($songs as $song)
        @include('partials.song-card', ['song' => $song])
        @endforeach
    </div>
    {{ $songs->links() }}
    @endif

</div>
@endsection
