<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChordDiagramResource;
use App\Http\Resources\SongResource;
use App\Models\ChordDiagram;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SongController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Song::with(['artist', 'category', 'defaultChord'])
            ->where('is_published', true);

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('artist_id')) {
            $query->where('artist_id', $request->artist_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('key')) {
            $query->where('key', $request->key);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        $sortField = in_array($request->sort, ['views', 'title', 'created_at'])
            ? $request->sort
            : 'created_at';

        $query->orderBy($sortField, $sortField === 'title' ? 'asc' : 'desc');

        return SongResource::collection($query->paginate(20));
    }

    public function show(string $slug): SongResource
    {
        $song = Song::with(['artist', 'category', 'chords'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $song->incrementViews();

        return new SongResource($song);
    }

    public function suggestions(string $slug): AnonymousResourceCollection
    {
        $song = Song::where('slug', $slug)->where('is_published', true)->firstOrFail();

        $suggestions = Song::with(['artist', 'category', 'defaultChord'])
            ->where('is_published', true)
            ->where('id', '!=', $song->id)
            ->where(function ($q) use ($song) {
                $q->where('artist_id', $song->artist_id)
                    ->orWhere('category_id', $song->category_id);
            })
            ->inRandomOrder()
            ->limit(5)
            ->get();

        return SongResource::collection($suggestions);
    }

    public function chordDiagrams(string $slug): AnonymousResourceCollection
    {
        $song = Song::with('defaultChord')->where('slug', $slug)->firstOrFail();

        $chord = $song->defaultChord;
        $chordNames = [];

        if ($chord) {
            preg_match_all('/\[([A-G][#b]?(?:m|M|maj|min|dim|aug|sus|add)?[0-9]*(?:\/[A-G][#b]?)?)\]/', $chord->content, $matches);
            $chordNames = array_unique($matches[1]);
        }

        $diagrams = ChordDiagram::whereIn('name', $chordNames)->get();

        return ChordDiagramResource::collection($diagrams);
    }
}
