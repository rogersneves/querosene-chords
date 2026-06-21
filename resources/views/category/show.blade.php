@extends('layouts.app')

@section('title', $category->name . ' — Cifras')
@section('description', 'As melhores cifras de ' . $category->name . ' no Querosene Chords.')

@section('content')
<div>

    {{-- Banner --}}
    <div class="py-12 px-4 border-b border-white/5" style="background: {{ $category->color }}0D;">
        <div class="max-w-7xl mx-auto flex items-center gap-4">
            <span class="w-5 h-5 rounded-full shrink-0" style="background: {{ $category->color }}"></span>
            <div>
                <h1 class="text-3xl font-black" style="color: {{ $category->color }}">{{ $category->name }}</h1>
                <p class="text-muted text-sm mt-0.5">{{ $songs->total() }} {{ $songs->total() === 1 ? 'cifra' : 'cifras' }}</p>
            </div>
        </div>
    </div>

    {{-- Grid --}}
    <div class="max-w-7xl mx-auto px-4 py-8">
        @if($songs->isEmpty())
        <p class="text-muted">Nenhuma cifra disponível nessa categoria.</p>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-6">
            @foreach($songs as $song)
            @include('partials.song-card', ['song' => $song])
            @endforeach
        </div>
        {{ $songs->links() }}
        @endif
    </div>

</div>
@endsection
