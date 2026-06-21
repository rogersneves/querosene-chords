<?php

namespace App\Livewire;

use App\Models\Artist;
use App\Models\Song;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class HeaderSearch extends Component
{
    public string $query = '';
    public array $suggestions = [];
    public bool $open = false;

    public function updatedQuery(): void
    {
        if (mb_strlen($this->query) < 2) {
            $this->suggestions = [];
            $this->open = false;
            return;
        }

        $term = '%' . $this->query . '%';

        $songs = Song::where('is_published', true)
            ->where('title', 'like', $term)
            ->with('artist')
            ->limit(4)
            ->get()
            ->map(fn ($s) => [
                'type'     => 'song',
                'label'    => $s->title,
                'sublabel' => $s->artist->name,
                'url'      => route('songs.show', $s),
            ]);

        $artists = Artist::where('name', 'like', $term)
            ->limit(3)
            ->get()
            ->map(fn ($a) => [
                'type'     => 'artist',
                'label'    => $a->name,
                'sublabel' => 'Artista',
                'url'      => route('artists.show', $a),
            ]);

        $this->suggestions = $songs->concat($artists)->take(5)->values()->toArray();
        $this->open = count($this->suggestions) > 0;
    }

    public function clear(): void
    {
        $this->query = '';
        $this->suggestions = [];
        $this->open = false;
    }

    public function render()
    {
        return view('livewire.header-search');
    }
}
