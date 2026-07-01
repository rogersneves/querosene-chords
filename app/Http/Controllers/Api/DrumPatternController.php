<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Services\ChordProDrumParser;
use App\Services\ChordProRenderer;
use App\Services\DrumPatternService;
use Illuminate\Http\JsonResponse;

class DrumPatternController extends Controller
{
    public function show(
        Song               $song,
        ChordProDrumParser $parser,
        DrumPatternService $drumService
    ): JsonResponse {
        $locale = substr(request()->header('Accept-Language', app()->getLocale()), 0, 2);

        $chordContent = $song->chords()
            ->where('is_default', true)
            ->value('content') ?? '';

        $chordContent = app(ChordProRenderer::class)->filterCommentsByLocale($chordContent, $locale);

        $parsed = $parser->parse($chordContent);

        $pattern = $drumService->getPattern(
            genre:     $parsed['genre'],
            bpm:       $parsed['bpm'],
            drumStyle: $parsed['drumStyle'],
        );

        return response()->json([
            'bpm'        => $parsed['bpm'],
            'style'      => $pattern['style'],
            'pattern'    => $pattern,
            'drum_hints' => $parsed['drumHints'],
            'bars_map'   => $parsed['barsMap'],
        ]);
    }
}
