<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SongResource;
use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->string('q')->trim()->toString();
        $type = $request->input('type', 'all');

        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $result = [];

        if (in_array($type, ['all', 'songs'])) {
            $result['songs'] = SongResource::collection(
                Song::with(['artist', 'category', 'defaultChord'])
                    ->where('is_published', true)
                    ->where(function ($query) use ($q) {
                        $query->where('title', 'like', "%{$q}%");
                    })
                    ->limit(10)
                    ->get()
            );
        }

        if (in_array($type, ['all', 'artists'])) {
            $result['artists'] = ArtistResource::collection(
                Artist::where('name', 'like', "%{$q}%")
                    ->withCount('songs')
                    ->limit(5)
                    ->get()
            );
        }

        if ($type === 'all') {
            $result['categories'] = CategoryResource::collection(
                Category::where('name', 'like', "%{$q}%")
                    ->withCount('songs')
                    ->limit(5)
                    ->get()
            );
        }

        return response()->json(['data' => $result]);
    }
}
