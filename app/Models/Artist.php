<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Artist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'bio', 'photo_path', 'country', 'genre', 'musicbrainz_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Artist $artist) {
            if (empty($artist->slug)) {
                $artist->slug = Str::slug($artist->name);
            }
        });
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
