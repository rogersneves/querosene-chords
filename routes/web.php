<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Web\ArtistController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\SongController as WebSongController;
use App\Livewire\SearchPage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/busca', SearchPage::class)->name('search');
Route::get('/artistas/{artist:slug}', [ArtistController::class, 'show'])->name('artists.show');
Route::get('/cifras/{song:slug}', [WebSongController::class, 'show'])->name('songs.show');
Route::get('/categorias/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
