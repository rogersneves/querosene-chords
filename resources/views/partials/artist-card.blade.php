<a href="{{ route('artists.show', $artist) }}"
   class="flex flex-col items-center text-center p-4 bg-surface rounded-xl border border-white/5 hover:border-white/10 hover:bg-white/5 transition-all group">
    <div class="w-14 h-14 rounded-full mb-3 overflow-hidden bg-primary/20 flex items-center justify-center text-primary font-black text-xl">
        @if($artist->photo_path)
        <img src="{{ Storage::disk('public')->url($artist->photo_path) }}" alt="{{ $artist->name }}" class="w-full h-full object-cover">
        @else
        {{ mb_substr($artist->name, 0, 1) }}
        @endif
    </div>
    <p class="text-sm font-bold text-[#F5F5F5] group-hover:text-primary transition-colors leading-tight">{{ $artist->name }}</p>
    @if(isset($artist->songs_count))
    <p class="text-xs text-muted mt-0.5">{{ $artist->songs_count }} {{ $artist->songs_count === 1 ? 'cifra' : 'cifras' }}</p>
    @endif
</a>
