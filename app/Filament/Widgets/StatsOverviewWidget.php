<?php

namespace App\Filament\Widgets;

use App\Models\Artist;
use App\Models\Category;
use App\Models\Import;
use App\Models\Song;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Músicas', Song::count())
                ->description(Song::where('is_published', true)->count() . ' publicadas')
                ->icon('heroicon-o-document-music')
                ->color('primary'),

            Stat::make('Artistas', Artist::count())
                ->icon('heroicon-o-musical-note')
                ->color('success'),

            Stat::make('Categorias', Category::count())
                ->icon('heroicon-o-tag')
                ->color('warning'),

            Stat::make('Importações', Import::count())
                ->description(Import::where('status', 'failed')->count() . ' com falha')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info'),
        ];
    }
}
