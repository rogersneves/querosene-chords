<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportResource\Pages;
use App\Models\Import;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Importações';
    protected static ?string $modelLabel = 'Importação';
    protected static ?string $pluralModelLabel = 'Importações';
    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->select([
                'id', 'original_filename', 'format',
                'total_files', 'imported_count', 'failed_count',
                'status', 'created_at',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('original_filename')
                    ->label('Arquivo')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('format')
                    ->label('Formato'),

                Tables\Columns\TextColumn::make('total_files')
                    ->label('Total'),

                Tables\Columns\TextColumn::make('imported_count')
                    ->label('Importados'),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Falhas'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImports::route('/'),
            'create' => Pages\CreateImport::route('/create'),
            'view' => Pages\ViewImport::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
