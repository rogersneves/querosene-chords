@extends('layouts.app')
@section('title', __('ui.setlist.my_title'))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black">{{ __('ui.setlist.my_title') }}</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-500/10 border border-green-500/20 text-green-400 rounded-xl px-4 py-3 text-sm mb-6">
        {{ session('success') }}
    </div>
    @endif

    {{-- Create form --}}
    <form method="POST" action="{{ route('setlists.store') }}"
          class="flex gap-2 mb-8">
        @csrf
        <input type="text" name="name" placeholder="{{ __('ui.setlist.new_placeholder') }}" required
               class="flex-1 bg-surface border border-white/10 rounded-xl px-4 py-2.5 text-sm
                      focus:outline-none focus:border-primary transition-colors
                      @error('name') border-red-500 @enderror">
        <button type="submit"
                class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors shrink-0">
            {{ __('ui.setlist.create_btn') }}
        </button>
    </form>

    {{-- List --}}
    @forelse($setlists as $setlist)
    <div class="bg-surface rounded-2xl border border-white/5 p-5 mb-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('setlists.show', $setlist) }}"
               class="font-semibold hover:text-primary transition-colors truncate block">
                {{ $setlist->name }}
            </a>
            <p class="text-xs text-muted mt-0.5">
                {{ trans_choice('ui.setlist.songs_count', $setlist->songs_count, ['count' => $setlist->songs_count]) }}
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('setlists.show', $setlist) }}"
               class="px-3 py-1.5 rounded-lg text-xs bg-white/5 hover:bg-white/10 transition-colors">
                {{ __('ui.setlist.open') }}
            </a>
            <form method="POST" action="{{ route('setlists.destroy', $setlist) }}">
                @csrf @method('DELETE')
                <button type="submit"
                        onclick="return confirm('{{ __('ui.setlist.confirm_delete') }}')"
                        class="px-3 py-1.5 rounded-lg text-xs text-red-400 hover:bg-red-500/10 transition-colors">
                    {{ __('ui.setlist.delete') }}
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="text-center py-12 text-muted">
        <p class="text-4xl mb-3">📋</p>
        <p>{{ __('ui.setlist.empty_hint') }}</p>
    </div>
    @endforelse

</div>
@endsection
