<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtistResource\Pages;
use App\Models\Artist;
use App\Services\Import\MusicMetadataService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
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
            Forms\Components\Section::make('Informações')
                ->footerActions([
                    Forms\Components\Actions\Action::make('enrich')
                        ->label('Enriquecer')
                        ->icon('heroicon-o-sparkles')
                        ->color('gray')
                        ->size('sm')
                        ->hidden(fn ($record) => $record === null)
                        ->action(function (Artist $record, \Livewire\Component $livewire): void {
                            $metadata = app(MusicMetadataService::class);

                            \Illuminate\Support\Facades\Cache::forget('mb_artist_'   . md5(mb_strtolower($record->name)));
                            \Illuminate\Support\Facades\Cache::forget('tadb_artist_' . md5(mb_strtolower($record->name)));

                            $artistMeta = $metadata->enrichArtist($record->name);

                            $updates = [];
                            if (! empty($artistMeta['genre']))           $updates['genre']          = $artistMeta['genre'];
                            if (! empty($artistMeta['bio']))             $updates['bio']             = $artistMeta['bio'];
                            if (! empty($artistMeta['bio_en']))          $updates['bio_en']          = $artistMeta['bio_en'];
                            if (! empty($artistMeta['bio_es']))          $updates['bio_es']          = $artistMeta['bio_es'];
                            if (! empty($artistMeta['bio_fr']))          $updates['bio_fr']          = $artistMeta['bio_fr'];
                            if (! empty($artistMeta['musicbrainz_id'])) $updates['musicbrainz_id']  = $artistMeta['musicbrainz_id'];
                            if (! empty($artistMeta['country']))         $updates['country']         = $artistMeta['country'];

                            if (! empty($artistMeta['photo_url']) && empty($record->photo_path)) {
                                $photoPath = $metadata->downloadArtistPhoto($artistMeta['photo_url'], $record->slug);
                                if ($photoPath) {
                                    $updates['photo_path'] = $photoPath;
                                }
                            }

                            if (! empty($updates)) {
                                $record->update($updates);
                            }

                            $count  = count($updates);
                            $labels = array_keys($updates);

                            Notification::make()
                                ->title($count > 0 ? "{$count} campo(s) atualizado(s)" : 'Nenhum dado novo encontrado')
                                ->body($count > 0 ? implode(', ', $labels) : null)
                                ->color($count > 0 ? 'success' : 'warning')
                                ->send();

                            if ($count > 0) {
                                $livewire->redirect(static::getUrl('edit', ['record' => $record]), navigate: true);
                            }
                        }),
                ])
                ->footerActionsAlignment(Alignment::End)
                ->schema([
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
                        ->placeholder('Preenchido automaticamente'),

                    Forms\Components\Tabs::make('Biografia')
                        ->columnSpanFull()
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Português')
                                ->schema([
                                    Forms\Components\RichEditor::make('bio')
                                        ->label('Português')
                                        ->disableToolbarButtons(['attachFiles']),
                                ]),
                            Forms\Components\Tabs\Tab::make('English')
                                ->schema([
                                    Forms\Components\Textarea::make('bio_en')
                                        ->label('English')
                                        ->rows(8)
                                        ->autosize(),
                                ]),
                            Forms\Components\Tabs\Tab::make('Español')
                                ->schema([
                                    Forms\Components\Textarea::make('bio_es')
                                        ->label('Español')
                                        ->rows(8)
                                        ->autosize(),
                                ]),
                            Forms\Components\Tabs\Tab::make('Français')
                                ->schema([
                                    Forms\Components\Textarea::make('bio_fr')
                                        ->label('Français')
                                        ->rows(8)
                                        ->autosize(),
                                ]),
                        ]),
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
