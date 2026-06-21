<?php

namespace App\Livewire;

use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Buscar Cifras')]
#[Layout('layouts.app')]
class SearchPage extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    #[Url]
    public string $tab = 'all';

    #[Url]
    public ?string $category = null;

    #[Url]
    public string $difficulty = '';

    #[Url]
    public string $key = '';

    public function updatedQuery(): void  { $this->resetPage(); }
    public function updatedCategory(): void { $this->resetPage(); }
    public function updatedDifficulty(): void { $this->resetPage(); }
    public function updatedKey(): void    { $this->resetPage(); }
    public function updatedTab(): void    { $this->resetPage(); }

    public function render()
    {
        $term = '%' . $this->query . '%';

        $songsQuery = Song::where('is_published', true)
            ->with(['artist', 'category'])
            ->when($this->query, fn ($q) => $q->where('title', 'like', $term))
            ->when($this->category, fn ($q) => $q->whereHas('category', fn ($r) => $r->where('slug', $this->category)))
            ->when($this->difficulty, fn ($q) => $q->where('difficulty', $this->difficulty))
            ->when($this->key, fn ($q) => $q->where('key', $this->key));

        $artistsQuery = Artist::withCount('songs')
            ->when($this->query, fn ($q) => $q->where('name', 'like', $term));

        $songs   = in_array($this->tab, ['all', 'songs'])   ? $songsQuery->paginate(20, pageName: 'sp')   : collect();
        $artists = in_array($this->tab, ['all', 'artists']) ? $artistsQuery->paginate(12, pageName: 'ap') : collect();

        $totalSongs   = in_array($this->tab, ['all', 'songs'])   ? $songsQuery->count()   : 0;
        $totalArtists = in_array($this->tab, ['all', 'artists']) ? $artistsQuery->count() : 0;

        return view('livewire.search-page', [
            'songs'        => $songs,
            'artists'      => $artists,
            'totalSongs'   => $totalSongs,
            'totalArtists' => $totalArtists,
            'categories'   => Category::orderBy('name')->get(),
            'keys'         => ['C','C#','D','D#','E','F','F#','G','G#','A','A#','B',
                               'Db','Eb','Gb','Ab','Bb'],
        ]);
    }
}
