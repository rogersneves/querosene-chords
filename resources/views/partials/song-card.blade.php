@php
$diffColors = ['iniciante' => '#22c55e', 'intermediário' => '#f59e0b', 'avançado' => '#ef4444'];
$color = $diffColors[$song->difficulty] ?? '#888';
@endphp
<a href="{{ route('songs.show', $song) }}"
   class="block bg-surface rounded-xl p-4 hover:bg-white/5 border border-white/5 hover:border-white/10 transition-all group">
    <div class="flex items-start justify-between gap-2 mb-1">
        <h3 class="font-bold text-[#F5F5F5] text-sm leading-tight truncate group-hover:text-primary transition-colors">
            {{ $song->title }}
        </h3>
        @if($song->key)
        <span class="shrink-0 text-xs bg-white/5 text-muted rounded px-1.5 py-0.5 font-mono">{{ $song->key }}</span>
        @endif
    </div>
    <p class="text-xs text-muted truncate mb-2">{{ $song->artist->name }}</p>
    <div class="flex items-center gap-2">
        <span class="text-[11px] font-semibold rounded px-1.5 py-0.5" style="background:{{ $color }}22;color:{{ $color }}">
            {{ ucfirst($song->difficulty) }}
        </span>
        @if($song->category)
        <span class="text-[11px] rounded px-1.5 py-0.5 truncate"
              style="background:{{ $song->category->color }}22;color:{{ $song->category->color }}">
            {{ $song->category->name }}
        </span>
        @endif
    </div>
</a>
