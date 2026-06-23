<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Song;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $popular = Song::with(['artist', 'category'])
            ->where('is_published', true)
            ->orderBy('views', 'desc')
            ->limit(8)
            ->get();

        $recent = Song::with(['artist', 'category'])
            ->where('is_published', true)
            ->latest()
            ->limit(10)
            ->get();

        $categories = Category::withCount(['songs' => fn ($q) => $q->where('is_published', true)])
            ->get();

        $total = Song::where('is_published', true)->count();

        return view('home', compact('popular', 'recent', 'categories', 'total'));
    }
}
