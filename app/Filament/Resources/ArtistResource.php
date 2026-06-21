<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtistResource\Pages;
use App\Models\Artist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;
    protected static ?string $navigationIcon = 'heroicon-o-musical-note';
    protected static ?string $navigationLabel = 'Artistas';
    protected static ?string $modelLabel = 'Artista';
    protected static ?string $pluralModelLabel = 'Artistas';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                        $set('slug', Str::slug($state))
                    ),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('genre')
                    ->label('Gênero')
                    ->maxLength(100),

                Forms\Components\TextInput::make('country')
                    ->label('País')
                    ->maxLength(2)
                    ->default('BR'),

                Forms\Components\FileUpload::make('photo_path')
                    ->label('Foto')
                    ->image()
                    ->directory('artists')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('musicbrainz_id')
                    ->label('MusicBrainz ID')
                    ->maxLength(36)
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Preenchido automaticamente na importação'),

                Forms\Components\RichEditor::make('bio')
                    ->label('Biografia')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('genre')
                    ->label('Gênero')
                    ->searchable(),

                Tables\Columns\TextColumn::make('songs_count')
                    ->label('Músicas')
                    ->counts('songs')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('genre')
                    ->label('Gênero')
                    ->options(fn () => Artist::whereNotNull('genre')
                        ->distinct()
                        ->pluck('genre', 'genre')
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('country')
                    ->label('País')
                    ->options(['BR' => 'Brasil', 'US' => 'EUA', 'UK' => 'Reino Unido']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArtists::route('/'),
            'create' => Pages\CreateArtist::route('/create'),
            'edit' => Pages\EditArtist::route('/{record}/edit'),
        ];
    }
}
