<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongResource;
use App\Models\Artist;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArtistController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $artists = Artist::withCount('songs')->orderBy('name')->paginate(20);

        return ArtistResource::collection($artists);
    }

    public function show(string $slug): ArtistResource
    {
        $artist = Artist::withCount('songs')->where('slug', $slug)->firstOrFail();

        return new ArtistResource($artist);
    }

    public function songs(string $slug): AnonymousResourceCollection
    {
        $artist = Artist::where('slug', $slug)->firstOrFail();

        $songs = $artist->songs()
            ->with(['category', 'defaultChord'])
            ->where('is_published', true)
            ->orderBy('title')
            ->paginate(20);

        return SongResource::collection($songs);
    }
}
