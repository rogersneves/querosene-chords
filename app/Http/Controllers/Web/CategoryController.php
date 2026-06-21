<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(Category $category): View
    {
        $songs = $category->songs()
            ->with('artist')
            ->where('is_published', true)
            ->orderBy('title')
            ->paginate(24);

        return view('category.show', compact('category', 'songs'));
    }
}
