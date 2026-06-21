<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SongResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::withCount('songs')->orderBy('name')->get();

        return CategoryResource::collection($categories);
    }

    public function songs(string $slug): AnonymousResourceCollection
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $songs = $category->songs()
            ->with(['artist', 'defaultChord'])
            ->where('is_published', true)
            ->orderBy('title')
            ->paginate(20);

        return SongResource::collection($songs);
    }
}
