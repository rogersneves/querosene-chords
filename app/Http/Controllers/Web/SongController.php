<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Services\ChordProRenderer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class SongController extends Controller
{
    public function show(Song $song, ChordProRenderer $renderer): View
    {
        abort_unless($song->is_published, 404);

        $song->load(['artist', 'category', 'defaultChord', 'chords']);
        $song->incrementViews();

        $content = $song->defaultChord?->content ?? '';

        $html = $content ? $renderer->render($content) : '';

        $youtubeId    = $song->youtube_id ?? $this->extractYoutubeId($content);
        $youtubeRatio = $youtubeId ? $this->fetchYoutubeRatio($youtubeId) : '16/9';

        $suggestions = Song::where('is_published', true)
            ->where('id', '!=', $song->id)
            ->where(fn ($q) => $q
                ->where('artist_id', $song->artist_id)
                ->orWhere('category_id', $song->category_id)
            )
            ->with('artist')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('song.show', compact('song', 'html', 'suggestions', 'youtubeId', 'youtubeRatio'));
    }

    private function fetchYoutubeRatio(string $youtubeId): string
    {
        return Cache::remember("yt_ratio_{$youtubeId}", now()->addDays(7), function () use ($youtubeId) {
            try {
                $response = Http::timeout(5)->get('https://www.youtube.com/oembed', [
                    'url'    => "https://www.youtube.com/watch?v={$youtubeId}",
                    'format' => 'json',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $w = (int) ($data['width']  ?? 16);
                    $h = (int) ($data['height'] ?? 9);
                    if ($w > 0 && $h > 0) {
                        return "{$w}/{$h}";
                    }
                }
            } catch (\Throwable) {
                // silently fall through to default
            }

            return '16/9';
        });
    }

    private function extractYoutubeId(string $content): ?string
    {
        // Matches youtube.com/watch?v=ID or youtu.be/ID anywhere in the file
        if (preg_match(
            '/https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?(?:[^\s}]*&)*v=([\w-]+)|youtu\.be\/([\w-]+))/',
            $content,
            $m
        )) {
            return $m[1] ?: $m[2];
        }

        return null;
    }
}
