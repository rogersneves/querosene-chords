<?php

namespace App\Filament\Widgets;

use App\Models\Import;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestImportsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static ?string $heading = 'Últimas importações';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Import::orderByDesc('created_at')->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('original_filename')->label('Arquivo'),
                Tables\Columns\TextColumn::make('format')->label('Formato')->badge(),
                Tables\Columns\TextColumn::make('imported_count')->label('Importados'),
                Tables\Columns\TextColumn::make('failed_count')->label('Falhas'),
                Tables\Columns\BadgeColumn::make('status')->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
            ]);
    }
}
