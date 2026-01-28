<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MangaAuthorController;
use App\Http\Controllers\Admin\MangaBadgeController;
use App\Http\Controllers\Admin\MangaCategoryController;
use App\Http\Controllers\Admin\MangaChapterController;
use App\Http\Controllers\Admin\MangaController;
use App\Http\Controllers\Admin\MangaServerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Mangas
    Route::resource('mangas', MangaController::class);

    // Manga Chapters
    Route::prefix('mangas/{manga}/chapters')->name('mangas.chapters.')->group(function () {
        Route::get('/', [MangaChapterController::class, 'index'])->name('index');
        Route::post('/', [MangaChapterController::class, 'store'])->name('store');
        Route::post('/upload-zip', [MangaChapterController::class, 'uploadZip'])->name('upload-zip');
        Route::post('/store-from-urls', [MangaChapterController::class, 'storeFromUrls'])->name('store-from-urls');
        Route::post('/reorder', [MangaChapterController::class, 'reorder'])->name('reorder');
        Route::put('/{chapter}', [MangaChapterController::class, 'update'])->name('update');
        Route::delete('/{chapter}', [MangaChapterController::class, 'destroy'])->name('destroy');
    });

    // Categories
    Route::resource('categories', MangaCategoryController::class);

    // Authors
    Route::resource('authors', MangaAuthorController::class);

    // Badges
    Route::resource('badges', MangaBadgeController::class);

    // Servers
    Route::resource('servers', MangaServerController::class);
});
