<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Setlist extends Model
{
    protected $fillable = ['user_id', 'name', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class, 'setlist_songs')
            ->withPivot(['position', 'semitones', 'font_size', 'scroll_speed', 'beginner_mode'])
            ->withTimestamps()
            ->orderBy('setlist_songs.position');
    }
}
