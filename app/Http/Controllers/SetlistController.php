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

        $validated = $request->validate([
            'song_id'      => ['required', 'exists:songs,id'],
            'semitones'    => ['integer', 'between:-6,6'],
            'font_size'    => ['integer', 'between:0,3'],
            'scroll_speed' => ['integer', 'between:0,10'],
            'beginner_mode' => ['boolean'],
        ]);

        $songId = $validated['song_id'];

        $settings = [
            'semitones'    => $validated['semitones'] ?? 0,
            'font_size'    => $validated['font_size'] ?? 1,
            'scroll_speed' => $validated['scroll_speed'] ?? 3,
            'beginner_mode' => $validated['beginner_mode'] ?? false,
        ];

        if ($setlist->songs()->where('song_id', $songId)->exists()) {
            $setlist->songs()->updateExistingPivot($songId, $settings);
            return response()->json(['updated' => true]);
        }

        if ($setlist->songs()->count() >= 30) {
            return response()->json(['added' => false, 'error' => 'limit'], 422);
        }

        $setlist->songs()->attach($songId, array_merge($settings, [
            'position' => $setlist->songs()->count(),
        ]));

        return response()->json(['added' => true]);
    }

    public function reorder(Request $request, Setlist $setlist): JsonResponse
    {
        abort_unless($setlist->user_id === auth()->id(), 403);

        $ids = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:songs,id'],
        ])['ids'];

        foreach ($ids as $position => $songId) {
            $setlist->songs()->updateExistingPivot($songId, ['position' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    public function removeSong(Setlist $setlist, Song $song): RedirectResponse
    {
        abort_unless($setlist->user_id === auth()->id(), 403);
        $setlist->songs()->detach($song->id);
        return back()->with('success', __('ui.setlist.song_removed'));
    }
}
