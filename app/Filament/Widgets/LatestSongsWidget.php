<?php

namespace App\Filament\Widgets;

use App\Models\Song;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSongsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Últimas músicas adicionadas';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Song::with(['artist'])->orderByDesc('created_at')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Título'),
                Tables\Columns\TextColumn::make('artist.name')->label('Artista'),
                Tables\Columns\TextColumn::make('created_at')->label('Adicionada em')->dateTime('d/m/Y H:i'),
            ]);
    }
}
