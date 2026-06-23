<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SongBrowserController extends Controller
{
    // Common chords shown in the picker (root notes + most used variations)
    private const CHORD_PALETTE = [
        'C', 'Cm', 'C7', 'Cm7', 'Cmaj7',
        'D', 'Dm', 'D7', 'Dm7',
        'E', 'Em', 'E7', 'Em7',
        'F', 'Fm', 'F7',
        'G', 'Gm', 'G7',
        'A', 'Am', 'A7', 'Am7',
        'B', 'Bm', 'B7', 'Bm7',
        'F#m', 'C#m', 'G#m', 'Bb', 'Eb',
    ];

    public function index(Request $request): View
    {
        $selected = array_filter((array) $request->input('chords', []));

        $query = Song::where('is_published', true)
            ->with(['artist', 'category'])
            ->orderByDesc('views');

        if (! empty($selected)) {
            // Keep only songs where every chord in chord_list is in $selected
            $query->whereNotNull('chord_list');
            $songs = $query->get()->filter(function (Song $song) use ($selected) {
                return empty(array_diff($song->chord_list ?? [], $selected));
            })->values();

            $paginated = null;
        } else {
            $paginated = $query->paginate(24)->withQueryString();
            $songs     = null;
        }

        return view('songs.index', [
            'palette'  => self::CHORD_PALETTE,
            'selected' => $selected,
            'paginated' => $paginated,
            'songs'    => $songs,
        ]);
    }
}
