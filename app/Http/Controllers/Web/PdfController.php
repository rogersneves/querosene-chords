<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setlist;
use App\Models\Song;
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

        $html     = app(ChordProRenderer::class)->render($chord->content);
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

        $songs = $setlist->songs->map(function (Song $song) {
            $chord    = $song->chords->firstWhere('is_default', true) ?? $song->chords->first();
            $html     = $chord ? app(ChordProRenderer::class)->render($chord->content) : '';
            $diagrams = $this->buildDiagrams($song->chord_list ?? []);
            return compact('song', 'html', 'diagrams');
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
