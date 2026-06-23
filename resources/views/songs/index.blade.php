@extends('layouts.app')

@section('title', __('ui.songs.browse_title'))

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <h1 class="text-2xl font-black mb-1">{{ __('ui.songs.browse_title') }}</h1>
    <p class="text-muted text-sm mb-6">{{ __('ui.songs.browse_subtitle') }}</p>

    {{-- ── Chord Picker ─────────────────────────────────────────────────── --}}
    <div x-data="chordPicker({{ json_encode($selected) }})"
         class="bg-surface rounded-2xl p-5 mb-8 border border-white/5">

        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-semibold text-[#F5F5F5]">{{ __('ui.songs.picker_label') }}</p>
            <button @click="clear()" x-show="chosen.length > 0"
                    class="text-xs text-muted hover:text-primary transition-colors">
                {{ __('ui.songs.picker_clear') }}
            </button>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($palette as $chord)
            <button
                @click="toggle('{{ $chord }}')"
                :class="chosen.includes('{{ $chord }}')
                    ? 'bg-primary text-white border-primary'
                    : 'bg-white/5 text-muted border-white/10 hover:border-primary/50 hover:text-[#F5F5F5]'"
                class="px-3 py-1.5 rounded-lg text-sm font-mono font-semibold border transition-all">
                {{ $chord }}
            </button>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('songs.browse') }}" x-ref="form" class="flex-1 flex items-center gap-3">
                <template x-for="c in chosen" :key="c">
                    <input type="hidden" name="chords[]" :value="c">
                </template>
                <button type="submit"
                        :disabled="chosen.length === 0"
                        class="px-5 py-2 rounded-xl bg-primary text-white font-bold text-sm transition-all
                               disabled:opacity-40 disabled:cursor-not-allowed hover:bg-primary/90">
                    {{ __('ui.songs.picker_search') }}
                </button>
                <span x-show="chosen.length > 0" x-text="'{{ __('ui.songs.picker_count') }}'.replace(':n', chosen.length)"
                      class="text-xs text-muted"></span>
            </form>
        </div>
    </div>

    {{-- ── Results ──────────────────────────────────────────────────────── --}}
    @php $list = $songs ?? $paginated; @endphp

    @if(!empty($selected))
    <p class="text-sm text-muted mb-4">
        {{ trans_choice('ui.songs.results_count', count($list), ['count' => count($list)]) }}
    </p>
    @endif

    @if($list && count($list) > 0)
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        @foreach($list as $song)
        @include('partials.song-card', ['song' => $song])
        @endforeach
    </div>

    @if($paginated)
    <div class="mt-6">{{ $paginated->links() }}</div>
    @endif

    @elseif(!empty($selected))
    <div class="text-center py-16 text-muted">
        <p class="text-4xl mb-4">🎸</p>
        <p class="font-semibold">{{ __('ui.songs.no_results') }}</p>
        <p class="text-sm mt-1">{{ __('ui.songs.no_results_hint') }}</p>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function chordPicker(initial) {
    return {
        chosen: initial,
        toggle(chord) {
            const i = this.chosen.indexOf(chord);
            if (i === -1) this.chosen.push(chord);
            else this.chosen.splice(i, 1);
        },
        clear() { this.chosen = []; },
    };
}
</script>
@endpush
