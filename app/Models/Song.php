<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Song extends Model
{
    use HasFactory;

    protected $fillable = [
        'artist_id', 'category_id', 'title', 'slug', 'key',
        'difficulty', 'bpm', 'year', 'album', 'musicbrainz_id', 'youtube_id',
        'is_published', 'views', 'chord_list',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'views'        => 'integer',
        'bpm'          => 'integer',
        'year'         => 'integer',
        'chord_list'   => 'array',
    ];

    /** Extract sorted unique chord tokens from a ChordPro string. */
    public static function extractChordList(string $content): array
    {
        preg_match_all('/\[([A-G][#b]?[^\]\/]*)\]/', $content, $m);
        $chords = array_unique(array_filter(array_map('trim', $m[1])));
        sort($chords);
        return array_values($chords);
    }

    protected static function booted(): void
    {
        static::creating(function (Song $song) {
            if (empty($song->slug)) {
                $song->slug = Str::slug($song->title);
            }
        });
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function chords(): HasMany
    {
        return $this->hasMany(Chord::class);
    }

    public function defaultChord(): HasOne
    {
        return $this->hasOne(Chord::class)->where('is_default', true);
    }

    public function setlists(): BelongsToMany
    {
        return $this->belongsToMany(Setlist::class, 'setlist_songs')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
