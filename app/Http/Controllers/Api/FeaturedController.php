<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResource;
use App\Models\Song;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeaturedController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        $songs = Song::with(['artist', 'category', 'defaultChord'])
            ->where('is_published', true)
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        return SongResource::collection($songs);
    }
}
