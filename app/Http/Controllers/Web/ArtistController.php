<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\View\View;

class ArtistController extends Controller
{
    public function show(Artist $artist): View
    {
        $songs = $artist->songs()
            ->with('category')
            ->where('is_published', true)
            ->orderBy('title')
            ->paginate(24);

        return view('artist.show', compact('artist', 'songs'));
    }
}
