<?php

namespace App\Filament\Resources\SongResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChordsRelationManager extends RelationManager
{
    protected static string $relationship = 'chords';
    protected static ?string $title = 'Versões da Cifra';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('version_label')
                ->label('Rótulo da versão')
                ->required()
                ->default('Padrão'),

            Forms\Components\Select::make('source')
                ->label('Fonte')
                ->options([
                    'manual' => 'Manual',
                    'cifraclub' => 'Cifra Club',
                    'chordpro' => 'ChordPro',
                    'guitarpro' => 'GuitarPro',
                    'musicxml' => 'MusicXML',
                ])
                ->default('manual'),

            Forms\Components\Toggle::make('is_default')
                ->label('Versão padrão')
                ->default(false),

            Forms\Components\Textarea::make('content')
                ->label('Conteúdo ChordPro')
                ->required()
                ->rows(20)
                ->extraAttributes(['style' => 'font-family: monospace; font-size: 13px;'])
                ->columnSpanFull(),

            Forms\Components\Textarea::make('tab_content')
                ->label('Tablatura (opcional)')
                ->rows(10)
                ->extraAttributes(['style' => 'font-family: monospace; font-size: 13px;'])
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version_label')
                    ->label('Versão'),

                Tables\Columns\TextColumn::make('source')
                    ->label('Fonte')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Padrão')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
