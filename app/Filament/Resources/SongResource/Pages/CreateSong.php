<?php

namespace App\Filament\Resources\SongResource\Pages;

use App\Filament\Resources\SongResource;
use App\Models\Chord;
use Filament\Resources\Pages\CreateRecord;

class CreateSong extends CreateRecord
{
    protected static string $resource = SongResource::class;

    protected function afterCreate(): void
    {
        $data = $this->data;

        if (!empty($data['chord_content'])) {
            Chord::create([
                'song_id' => $this->record->id,
                'content' => $data['chord_content'],
                'version_label' => 'Padrão',
                'source' => $data['chord_source'] ?? 'manual',
                'is_default' => true,
            ]);
        }
    }
}
