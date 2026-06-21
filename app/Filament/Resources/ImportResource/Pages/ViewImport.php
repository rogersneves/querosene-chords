<?php

namespace App\Filament\Resources\ImportResource\Pages;

use App\Filament\Resources\ImportResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewImport extends ViewRecord
{
    protected static string $resource = ImportResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Resumo')->schema([
                Infolists\Components\TextEntry::make('original_filename')->label('Arquivo')
                    ->columnSpanFull()
                    ->copyable(),
                Infolists\Components\TextEntry::make('format')->label('Formato')->badge(),
                Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    }),
                Infolists\Components\TextEntry::make('total_files')->label('Total de arquivos'),
                Infolists\Components\TextEntry::make('imported_count')->label('Importados'),
                Infolists\Components\TextEntry::make('failed_count')->label('Falhas'),
                Infolists\Components\TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
            ])->columns(3),

            Infolists\Components\Section::make('Log detalhado')->schema([
                Infolists\Components\RepeatableEntry::make('log')
                    ->label('')
                    ->schema([
                        Infolists\Components\TextEntry::make('file')->label('Arquivo')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                            ->color(fn ($state) => $state === 'ok' ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('message')->label('Erro')
                            ->hidden(fn ($state) => blank($state))
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('from_file')
                            ->label('Do arquivo')
                            ->html()
                            ->formatStateUsing(fn ($state) =>
                                is_array($state) && count($state)
                                    ? collect($state)->map(fn ($v, $k) => '<b>' . e($k) . '</b>: ' . e($v))->join('<br>')
                                    : null
                            )
                            ->hidden(fn ($state) => empty($state)),
                        Infolists\Components\TextEntry::make('from_api')
                            ->label('MusicBrainz API')
                            ->html()
                            ->formatStateUsing(fn ($state) =>
                                is_array($state) && count($state)
                                    ? collect($state)->map(fn ($v, $k) => '<b>' . e($k) . '</b>: ' . e($v))->join('<br>')
                                    : null
                            )
                            ->hidden(fn ($state) => empty($state)),
                    ])->columns(2),
            ]),
        ]);
    }
}
