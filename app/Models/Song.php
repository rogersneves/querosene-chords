<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Song extends Model
{
    use HasFactory;

    protected $fillable = [
        'artist_id', 'category_id', 'title', 'slug', 'key',
        'difficulty', 'bpm', 'year', 'album', 'musicbrainz_id', 'youtube_id',
        'is_published', 'views',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'views' => 'integer',
        'bpm' => 'integer',
        'year' => 'integer',
    ];

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

    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
