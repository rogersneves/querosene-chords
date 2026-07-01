<?php

use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DrumPatternController;
use App\Http\Controllers\Api\FeaturedController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SongController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/featured', FeaturedController::class);
    Route::get('/search', SearchController::class);

    Route::get('/songs', [SongController::class, 'index']);
    Route::get('/songs/{slug}', [SongController::class, 'show']);
    Route::get('/songs/{slug}/suggestions', [SongController::class, 'suggestions']);
    Route::get('/songs/{slug}/chord-diagrams', [SongController::class, 'chordDiagrams']);
    Route::get('/songs/{song}/drum-pattern', [DrumPatternController::class, 'show']);

    Route::get('/artists', [ArtistController::class, 'index']);
    Route::get('/artists/{slug}', [ArtistController::class, 'show']);
    Route::get('/artists/{slug}/songs', [ArtistController::class, 'songs']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}/songs', [CategoryController::class, 'songs']);
});
