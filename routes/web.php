<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\KaraokeController;
use App\Http\Controllers\SongSearchController;
use App\Http\Controllers\SongCatalogController;
use App\Http\Controllers\ArtistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/songs', [SongCatalogController::class, 'index'])->name('songs.index');
Route::get('/songs/{videoId}', [SongCatalogController::class, 'show'])
    ->where('videoId', '[A-Za-z0-9_-]+')
    ->name('songs.show');
Route::get('/artists/{artistName}', [ArtistController::class, 'show'])
    ->where('artistName', '[A-Za-z0-9%._ -]+')
    ->name('artists.show');

Route::get('/karaoke', [KaraokeController::class, 'index'])->name('karaoke.index');
Route::get('/karaoke/search', SongSearchController::class)
    ->middleware('throttle:30,1')
    ->name('karaoke.search');
Route::post('/karaoke/play-history', [KaraokeController::class, 'storePlayHistory'])
    ->middleware('throttle:60,1')
    ->name('karaoke.play-history');
Route::post('/karaoke/favorites', [KaraokeController::class, 'storeFavorite'])
    ->middleware('throttle:60,1')
    ->name('karaoke.favorites.store');
Route::delete('/karaoke/favorites/{videoId}', [KaraokeController::class, 'destroyFavorite'])
    ->where('videoId', '[A-Za-z0-9_-]+')
    ->middleware('throttle:60,1')
    ->name('karaoke.favorites.destroy');
Route::post('/karaoke/queue', [KaraokeController::class, 'storeQueueItem'])
    ->middleware('throttle:60,1')
    ->name('karaoke.queue.store');
Route::patch('/karaoke/queue/{queueItem}', [KaraokeController::class, 'updateQueueItem'])
    ->whereNumber('queueItem')
    ->middleware('throttle:90,1')
    ->name('karaoke.queue.update');
Route::delete('/karaoke/queue/{queueItem}', [KaraokeController::class, 'destroyQueueItem'])
    ->whereNumber('queueItem')
    ->middleware('throttle:90,1')
    ->name('karaoke.queue.destroy');
