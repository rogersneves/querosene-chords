<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- Título + campo de busca --}}
    <h1 class="text-2xl font-black mb-6">Buscar</h1>

    <div class="relative mb-6">
        <svg class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l4.387 4.387a1 1 0 01-1.414 1.414l-4.387-4.387zM8 14A6 6 0 108 2a6 6 0 000 12z"/>
        </svg>
        <input
            wire:model.live.debounce.400ms="query"
            type="search"
            placeholder="Buscar cifras, artistas…"
            autocomplete="off"
            class="w-full bg-surface border border-white/10 rounded-xl pl-12 pr-4 py-3 text-[#F5F5F5] placeholder-muted focus:outline-none focus:border-primary/60 transition-colors"
        >
        <div wire:loading class="absolute right-4 top-1/2 -translate-y-1/2">
            <svg class="w-5 h-5 text-muted animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <select wire:model.live="category" class="bg-surface border border-white/10 text-sm rounded-lg px-3 py-1.5 text-[#F5F5F5] focus:outline-none focus:border-primary/50">
            <option value="">Todas as categorias</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="difficulty" class="bg-surface border border-white/10 text-sm rounded-lg px-3 py-1.5 text-[#F5F5F5] focus:outline-none focus:border-primary/50">
            <option value="">Qualquer dificuldade</option>
            <option value="iniciante">Iniciante</option>
            <option value="intermediário">Intermediário</option>
            <option value="avançado">Avançado</option>
        </select>

        <select wire:model.live="key" class="bg-surface border border-white/10 text-sm rounded-lg px-3 py-1.5 text-[#F5F5F5] focus:outline-none focus:border-primary/50">
            <option value="">Qualquer tom</option>
            @foreach($keys as $k)
            <option value="{{ $k }}">{{ $k }}</option>
            @endforeach
        </select>
    </div>

    {{-- Abas --}}
    <div class="flex gap-1 border-b border-white/10 mb-6">
        @foreach(['all' => 'Todas', 'songs' => 'Músicas', 'artists' => 'Artistas'] as $t => $label)
        <button
            wire:click="$set('tab', '{{ $t }}')"
            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
                {{ $tab === $t ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-[#F5F5F5]' }}"
        >
            {{ $label }}
            @if($t === 'songs' && $totalSongs > 0)
                <span class="ml-1 text-xs bg-primary/20 text-primary rounded-full px-1.5 py-0.5">{{ $totalSongs }}</span>
            @endif
            @if($t === 'artists' && $totalArtists > 0)
                <span class="ml-1 text-xs bg-secondary/20 text-secondary rounded-full px-1.5 py-0.5">{{ $totalArtists }}</span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- Resultados: Músicas --}}
    @if(in_array($tab, ['all', 'songs']))
    <div wire:loading.class="opacity-50" wire:target="query,category,difficulty,key,tab">
        @if($songs instanceof \Illuminate\Pagination\LengthAwarePaginator ? $songs->isEmpty() : $songs->isEmpty())
            @if($query)
            <div class="text-center py-12 text-muted">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" viewBox="0 0 24 24" fill="currentColor"><path d="M9.5 3A6.5 6.5 0 0116 9.5c0 1.61-.59 3.09-1.56 4.23l.27.27h.79l5 5-1.5 1.5-5-5v-.79l-.27-.27A6.516 6.516 0 019.5 16 6.5 6.5 0 013 9.5 6.5 6.5 0 019.5 3m0 2C7 5 5 7 5 9.5S7 14 9.5 14 14 12 14 9.5 12 5 9.5 5z"/></svg>
                <p>Nenhuma música encontrada para <strong>"{{ $query }}"</strong></p>
                <p class="text-sm mt-1">Tente um termo diferente ou verifique os filtros.</p>
            </div>
            @endif
        @else
        @if($tab === 'all' && $songs->count())
        <h2 class="text-sm font-bold text-muted uppercase tracking-wider mb-3">Músicas</h2>
        @endif
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
            @foreach($songs as $song)
            @include('partials.song-card', ['song' => $song])
            @endforeach
        </div>
        {{ $songs->links() }}
        @endif
    </div>
    @endif

    {{-- Resultados: Artistas --}}
    @if(in_array($tab, ['all', 'artists']))
    <div wire:loading.class="opacity-50" wire:target="query,tab">
        @if(($artists instanceof \Illuminate\Pagination\LengthAwarePaginator ? $artists->isEmpty() : $artists->isEmpty()) && $query)
            <p class="text-muted text-sm mt-4">Nenhum artista encontrado.</p>
        @elseif(!($artists instanceof \Illuminate\Pagination\LengthAwarePaginator ? $artists->isEmpty() : $artists->isEmpty()))
        @if($tab === 'all' && $artists->count())
        <h2 class="text-sm font-bold text-muted uppercase tracking-wider mb-3 mt-6">Artistas</h2>
        @endif
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-4">
            @foreach($artists as $artist)
            @include('partials.artist-card', ['artist' => $artist])
            @endforeach
        </div>
        {{ $artists->links() }}
        @endif
    </div>
    @endif

    @if(!$query)
    <div class="text-center py-20 text-muted">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-20" viewBox="0 0 24 24" fill="currentColor"><path d="M9.5 3A6.5 6.5 0 0116 9.5c0 1.61-.59 3.09-1.56 4.23l.27.27h.79l5 5-1.5 1.5-5-5v-.79l-.27-.27A6.516 6.516 0 019.5 16 6.5 6.5 0 013 9.5 6.5 6.5 0 019.5 3m0 2C7 5 5 7 5 9.5S7 14 9.5 14 14 12 14 9.5 12 5 9.5 5z"/></svg>
        <p class="text-lg">Digite algo para começar a buscar</p>
        <p class="text-sm mt-1">Artistas, músicas, tons…</p>
    </div>
    @endif
</div>
