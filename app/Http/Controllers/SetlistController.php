<?php

namespace App\Http\Controllers;

use App\Models\Setlist;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetlistController extends Controller
{
    public function index(): View
    {
        $setlists = auth()->user()
            ->setlists()
            ->withCount('songs')
            ->latest()
            ->get();

        return view('setlists.index', compact('setlists'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        auth()->user()->setlists()->create($data);

        return back()->with('success', __('ui.setlist.created'));
    }

    public function show(Setlist $setlist): View
    {
        abort_unless($setlist->user_id === auth()->id(), 403);

        $setlist->load(['songs.artist', 'songs.category']);

        return view('setlists.show', compact('setlist'));
    }

    public function destroy(Setlist $setlist): RedirectResponse
    {
        abort_unless($setlist->user_id === auth()->id(), 403);
        $setlist->delete();
        return redirect()->route('setlists.index')->with('success', __('ui.setlist.deleted'));
    }

    public function rename(Request $request, Setlist $setlist): RedirectResponse
    {
        abort_unless($setlist->user_id === auth()->id(), 403);
        $setlist->update($request->validate(['name' => ['required', 'string', 'max:100']]));
        return back()->with('success', __('ui.setlist.renamed'));
    }

    /** Toggle: add if not in list, remove if already there. Returns JSON. */
    public function toggle(Request $request, Setlist $setlist): JsonResponse
    {
        abort_unless($setlist->user_id === auth()->id(), 403);

        $songId = $request->validate(['song_id' => ['required', 'exists:songs,id']])['song_id'];

        if ($setlist->songs()->where('song_id', $songId)->exists()) {
            $setlist->songs()->detach($songId);
            $added = false;
        } else {
            $position = $setlist->songs()->count();
            $setlist->songs()->attach($songId, ['position' => $position]);
            $added = true;
        }

        return response()->json(['added' => $added]);
    }

    public function removeSong(Setlist $setlist, Song $song): RedirectResponse
    {
        abort_unless($setlist->user_id === auth()->id(), 403);
        $setlist->songs()->detach($song->id);
        return back()->with('success', __('ui.setlist.song_removed'));
    }
}
