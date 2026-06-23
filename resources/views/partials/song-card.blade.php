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
        @if($song->youtube_id)
        <svg class="ml-auto shrink-0" width="28" height="28" viewBox="0 0 24 24" fill="#ef4444" title="Vídeo disponível"><path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/></svg>
        @endif
    </div>
</a>
