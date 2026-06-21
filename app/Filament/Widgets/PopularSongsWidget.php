<?php

namespace App\Filament\Widgets;

use App\Models\Song;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularSongsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top 10 mais acessadas';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Song::with(['artist'])->orderByDesc('views')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Título'),
                Tables\Columns\TextColumn::make('artist.name')->label('Artista'),
                Tables\Columns\TextColumn::make('views')->label('Views')->sortable(),
            ]);
    }
}
