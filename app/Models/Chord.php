<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chord extends Model
{
    use HasFactory;

    protected $fillable = [
        'song_id', 'content', 'version_label', 'source', 'tab_content', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Chord $chord) {
            if ($chord->is_default) {
                static::where('song_id', $chord->song_id)
                    ->where('id', '!=', $chord->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
