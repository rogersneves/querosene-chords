<?php

namespace App\Filament\Resources\SongResource\Pages;

use App\Filament\Resources\SongResource;
use App\Models\Chord;
use App\Models\Song;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSong extends EditRecord
{
    protected static string $resource = SongResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $defaultChord = $this->record->defaultChord;

        if ($defaultChord) {
            $data['chord_content'] = $defaultChord->content;
            $data['chord_source'] = $defaultChord->source;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->data;

        if (!empty($data['chord_content'])) {
            Chord::updateOrCreate(
                ['song_id' => $this->record->id, 'is_default' => true],
                [
                    'content' => $data['chord_content'],
                    'source' => $data['chord_source'] ?? 'manual',
                    'version_label' => 'Padrão',
                ]
            );
            $this->record->updateQuietly(['chord_list' => Song::extractChordList($data['chord_content'])]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
