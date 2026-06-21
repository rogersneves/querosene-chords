<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'photo_url' => $this->photo_path ? asset('storage/' . $this->photo_path) : null,
            'country' => $this->country,
            'genre' => $this->genre,
            'songs_count' => $this->whenCounted('songs'),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
