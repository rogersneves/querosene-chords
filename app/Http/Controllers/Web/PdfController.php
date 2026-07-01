<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setlist;
use App\Models\Song;
use App\Services\BeginnerModeService;
use App\Services\ChordDiagramSvg;
use App\Services\ChordProRenderer;
use App\Services\Import\ChordDictionary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PdfController extends Controller
{
    public function song(Song $song): Response
    {
        $chord = $song->defaultChord ?? $song->chords()->first();
        abort_if(!$chord, 404);

        $html     = app(ChordProRenderer::class)->render($chord->content, app()->getLocale());
        $diagrams = $this->buildDiagrams($song->chord_list ?? []);

        $pdf = Pdf::loadView('pdf.song', compact('song', 'html', 'diagrams'))
            ->setPaper('a4', 'portrait');

        $filename = Str::slug($song->artist->name . '-' . $song->title) . '.pdf';
        return $pdf->download($filename);
    }

    public function setlist(Setlist $setlist): Response
    {
        abort_unless($setlist->user_id === auth()->id(), 403);

        $setlist->load(['songs.artist', 'songs.category', 'songs.chords']);

        $beginner = app(BeginnerModeService::class);
        $renderer = app(ChordProRenderer::class);

        $songs = $setlist->songs->map(function (Song $song) use ($beginner, $renderer) {
            $semitones = (int) ($song->pivot->semitones ?? 0);
            $chord     = $song->chords->firstWhere('is_default', true) ?? $song->chords->first();

            $content = $chord?->content ?? '';
            if ($semitones !== 0) {
                $content = $beginner->transposeContent($content, $semitones);
            }

            $html = $content ? $renderer->render($content, app()->getLocale()) : '';

            $chordList = $song->chord_list ?? [];
            if ($semitones !== 0) {
                $chordList = array_map(fn($c) => $beginner->transposeKey($c, $semitones), $chordList);
            }

            $diagrams = $this->buildDiagrams($chordList);
            $key      = $song->key ? $beginner->transposeKey($song->key, $semitones) : null;

            return compact('song', 'html', 'diagrams', 'key');
        });

        $pdf = Pdf::loadView('pdf.setlist', compact('setlist', 'songs'))
            ->setPaper('a4', 'portrait');

        $filename = Str::slug($setlist->name) . '.pdf';
        return $pdf->download($filename);
    }

    private function buildDiagrams(array $chordList): array
    {
        $dict     = ChordDictionary::all();
        $diagrams = [];
        foreach ($chordList as $name) {
            if (isset($dict[$name])) {
                $diagrams[$name] = ChordDiagramSvg::render($name, $dict[$name]['pattern'], $dict[$name]['barre']);
            }
        }
        return $diagrams;
    }
}
