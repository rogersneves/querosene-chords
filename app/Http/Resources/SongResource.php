<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SongResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'key' => $this->key,
            'difficulty' => $this->difficulty,
            'bpm' => $this->bpm,
            'year' => $this->year,
            'views' => $this->views,
            'is_published' => $this->is_published,
            'artist' => new ArtistResource($this->whenLoaded('artist')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'chord' => new ChordResource($this->whenLoaded('defaultChord')),
            'chords' => ChordResource::collection($this->whenLoaded('chords')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
