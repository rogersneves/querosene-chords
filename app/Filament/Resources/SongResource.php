<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SongResource\Pages;
use App\Filament\Resources\SongResource\RelationManagers\ChordsRelationManager;
use App\Models\Song;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SongResource extends Resource
{
    protected static ?string $model = Song::class;
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Músicas';
    protected static ?string $modelLabel = 'Música';
    protected static ?string $pluralModelLabel = 'Músicas';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informações')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                            $set('slug', Str::slug($state))
                        ),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('artist_id')
                        ->label('Artista')
                        ->relationship('artist', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('category_id')
                        ->label('Categoria')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Forms\Components\Select::make('key')
                        ->label('Tom')
                        ->options([
                            'C' => 'C', 'C#' => 'C#', 'Db' => 'Db',
                            'D' => 'D', 'D#' => 'D#', 'Eb' => 'Eb',
                            'E' => 'E', 'F' => 'F', 'F#' => 'F#',
                            'Gb' => 'Gb', 'G' => 'G', 'G#' => 'G#',
                            'Ab' => 'Ab', 'A' => 'A', 'A#' => 'A#',
                            'Bb' => 'Bb', 'B' => 'B',
                        ])
                        ->nullable(),

                    Forms\Components\Select::make('difficulty')
                        ->label('Dificuldade')
                        ->options([
                            'iniciante' => 'Iniciante',
                            'intermediário' => 'Intermediário',
                            'avançado' => 'Avançado',
                        ])
                        ->default('intermediário')
                        ->required(),

                    Forms\Components\TextInput::make('bpm')
                        ->label('BPM')
                        ->numeric()
                        ->minValue(20)
                        ->maxValue(300),

                    Forms\Components\TextInput::make('year')
                        ->label('Ano')
                        ->numeric()
                        ->minValue(1900)
                        ->maxValue(date('Y')),

                    Forms\Components\TextInput::make('album')
                        ->label('Álbum')
                        ->maxLength(255),

                    Forms\Components\Toggle::make('is_published')
                        ->label('Publicado')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('Cifra Padrão')
                ->schema([
                    Forms\Components\Select::make('chord_source')
                        ->label('Fonte')
                        ->options([
                            'manual' => 'Manual',
                            'cifraclub' => 'Cifra Club',
                            'chordpro' => 'ChordPro',
                            'guitarpro' => 'GuitarPro',
                            'musicxml' => 'MusicXML',
                        ])
                        ->default('manual')
                        ->dehydrated(false),

                    Forms\Components\Textarea::make('chord_content')
                        ->label('Conteúdo ChordPro')
                        ->rows(20)
                        ->extraAttributes(['style' => 'font-family: monospace; font-size: 13px;'])
                        ->columnSpanFull()
                        ->dehydrated(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('artist.name')
                    ->label('Artista')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?? '#FF6D00'),

                Tables\Columns\TextColumn::make('album')
                    ->label('Álbum')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('key')
                    ->label('Tom'),

                Tables\Columns\BadgeColumn::make('difficulty')
                    ->label('Dificuldade')
                    ->colors([
                        'success' => 'iniciante',
                        'warning' => 'intermediário',
                        'danger' => 'avançado',
                    ]),

                Tables\Columns\TextColumn::make('views')
                    ->label('Views')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_published')
                    ->label('Publicado'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist_id')
                    ->label('Artista')
                    ->relationship('artist', 'name')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('difficulty')
                    ->label('Dificuldade')
                    ->options([
                        'iniciante' => 'Iniciante',
                        'intermediário' => 'Intermediário',
                        'avançado' => 'Avançado',
                    ]),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Publicado'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_web')
                    ->label('Ver na web')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Song $record) => url('/cifras/' . $record->slug))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelationManagers(): array
    {
        return [
            ChordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSongs::route('/'),
            'create' => Pages\CreateSong::route('/create'),
            'edit' => Pages\EditSong::route('/{record}/edit'),
        ];
    }
}
