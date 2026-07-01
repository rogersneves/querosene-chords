<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SetlistController;
use App\Http\Controllers\Web\ArtistController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\PdfController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\SongBrowserController;
use App\Http\Controllers\Web\SongController as WebSongController;
use App\Livewire\SearchPage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/calculadora-de-capo', fn() => view('tools.capo'))->name('tools.capo');
Route::get('/busca', SearchPage::class)->name('search');
Route::get('/explorar', [SongBrowserController::class, 'index'])->name('songs.browse');
Route::get('/artistas/{artist:slug}', [ArtistController::class, 'show'])->name('artists.show');
Route::get('/cifras/{song:slug}', [WebSongController::class, 'show'])->name('songs.show');
Route::get('/categorias/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/cifras/{song:slug}/pdf', [PdfController::class, 'song'])->name('songs.pdf');
Route::get('/lang/{locale}', [LocaleController::class, 'set'])->name('locale.set')->where('locale', 'pt|en|es|fr');

// Auth público
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [LoginController::class, 'create'])->name('login');
    Route::post('/entrar', [LoginController::class, 'store']);
    Route::get('/cadastrar', [RegisterController::class, 'create'])->name('register');
    Route::post('/cadastrar', [RegisterController::class, 'store']);
});
Route::post('/sair', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

// MFA (sem guard guest — o mfa_user_id na session controla acesso)
Route::get('/verificar', [MfaController::class, 'create'])->name('mfa.verify');
Route::post('/verificar', [MfaController::class, 'store'])->name('mfa.verify.store');
Route::post('/verificar/reenviar', [MfaController::class, 'resend'])->name('mfa.resend');

// Caderno (setlists) — requer auth
Route::middleware('auth')->prefix('caderno')->name('setlists.')->group(function () {
    Route::get('/', [SetlistController::class, 'index'])->name('index');
    Route::post('/', [SetlistController::class, 'store'])->name('store');
    Route::get('/{setlist}', [SetlistController::class, 'show'])->name('show');
    Route::delete('/{setlist}', [SetlistController::class, 'destroy'])->name('destroy');
    Route::patch('/{setlist}/renomear', [SetlistController::class, 'rename'])->name('rename');
    Route::post('/{setlist}/toggle', [SetlistController::class, 'toggle'])->name('toggle');
    Route::delete('/{setlist}/musica/{song}', [SetlistController::class, 'removeSong'])->name('remove-song');
    Route::post('/{setlist}/reordenar', [SetlistController::class, 'reorder'])->name('reorder');
    Route::get('/{setlist}/pdf', [PdfController::class, 'setlist'])->name('pdf');
});
