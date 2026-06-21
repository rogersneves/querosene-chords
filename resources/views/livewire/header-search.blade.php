<div class="relative w-full" x-data @keydown.escape.window="$wire.clear()">
    <div class="relative">
        <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l4.387 4.387a1 1 0 01-1.414 1.414l-4.387-4.387zM8 14A6 6 0 108 2a6 6 0 000 12z"/>
        </svg>
        <input
            wire:model.live.debounce.300ms="query"
            type="search"
            placeholder="Buscar cifras e artistas…"
            autocomplete="off"
            class="w-full bg-[#0D0D0D] border border-white/10 rounded-xl pl-9 pr-9 py-2 text-sm text-[#F5F5F5] placeholder-muted focus:outline-none focus:border-primary/60 transition-colors"
        >
        @if($query)
        <button
            wire:click="clear"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-[#F5F5F5] transition-colors"
            aria-label="Limpar"
        >
            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
            </svg>
        </button>
        @endif
    </div>

    @if($open && count($suggestions))
    <div class="absolute top-full left-0 right-0 mt-1 bg-surface border border-white/10 rounded-xl shadow-2xl overflow-hidden z-50">
        @foreach($suggestions as $s)
        <a
            href="{{ $s['url'] }}"
            wire:click="clear"
            class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 transition-colors"
        >
            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $s['type'] === 'artist' ? 'bg-secondary/20' : 'bg-primary/20' }}">
                @if($s['type'] === 'artist')
                <svg class="w-4 h-4 text-secondary" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                @else
                <svg class="w-4 h-4 text-primary" viewBox="0 0 20 20" fill="currentColor"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/></svg>
                @endif
            </span>
            <span class="min-w-0">
                <span class="block text-sm text-[#F5F5F5] truncate">{{ $s['label'] }}</span>
                <span class="block text-xs text-muted truncate">{{ $s['sublabel'] }}</span>
            </span>
        </a>
        @endforeach

        <a
            href="{{ route('search', ['q' => $query]) }}"
            wire:click="clear"
            class="flex items-center justify-center gap-1.5 px-4 py-2.5 border-t border-white/5 text-xs text-primary hover:bg-white/5 transition-colors"
        >
            Ver todos os resultados para <strong>"{{ $query }}"</strong>
        </a>
    </div>
    @endif
</div>
