<?php

use App\Http\Controllers\Admin\CoinController;
use App\Http\Controllers\Admin\CoinRequestController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\MangaAuthorController;
use App\Http\Controllers\Admin\MangaBadgeController;
use App\Http\Controllers\Admin\MangaCategoryController;
use App\Http\Controllers\Admin\MangaChapterController;
use App\Http\Controllers\Admin\MangaController;
use App\Http\Controllers\Admin\MangaServerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Mangas
    Route::prefix('mangas')->name('mangas.')->group(function () {
        Route::get('/', [MangaController::class, 'index'])->name('index');
        Route::get('/create', [MangaController::class, 'create'])->name('create');
        Route::post('/', [MangaController::class, 'store'])->name('store');
        Route::get('/{manga}', [MangaController::class, 'show'])->name('show');
        Route::get('/{manga}/edit', [MangaController::class, 'edit'])->name('edit');
        Route::put('/{manga}', [MangaController::class, 'update'])->name('update');
        Route::delete('/{manga}', [MangaController::class, 'destroy'])->name('destroy');
    });

    // Manga Chapters
    Route::prefix('mangas/{manga}/chapters')->name('mangas.chapters.')->group(function () {
        Route::get('/', [MangaChapterController::class, 'index'])->name('index');
        Route::post('/', [MangaChapterController::class, 'store'])->name('store');
        Route::post('/upload-zip', [MangaChapterController::class, 'uploadZip'])->name('upload-zip');
        Route::post('/upload-multiple-zip', [MangaChapterController::class, 'uploadMultipleZip'])->name('upload-multiple-zip');
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

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::get('/{user}/add-coin', [UserController::class, 'addCoinPage'])->name('add-coin-page');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/add-coin', [UserController::class, 'addCoin'])->name('add-coin');
        Route::post('/{user}/remove-coin', [UserController::class, 'removeCoin'])->name('remove-coin');
        Route::post('/{user}/assign-role', [UserController::class, 'assignRole'])->name('assign-role');
    });

    // Comments
    Route::prefix('comments')->name('comments.')->group(function () {
        Route::get('/', [CommentController::class, 'index'])->name('index');
        Route::get('/{comment}', [CommentController::class, 'show'])->name('show');
        Route::put('/{comment}', [CommentController::class, 'update'])->name('update');
        Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('destroy');
        Route::post('/{comment}/pin', [CommentController::class, 'pin'])->name('pin');
        Route::post('/{comment}/unpin', [CommentController::class, 'unpin'])->name('unpin');
    });

    // Coins
    Route::prefix('coins')->name('coins.')->group(function () {
        Route::get('/', [CoinController::class, 'index'])->name('index');
        Route::get('/users/{user}/transactions', [CoinController::class, 'userTransactions'])->name('user-transactions');
    });

    // Coin Requests
    Route::prefix('coin-requests')->name('coin-requests.')->group(function () {
        Route::get('/', [CoinRequestController::class, 'index'])->name('index');
        Route::get('/{coinRequest}', [CoinRequestController::class, 'show'])->name('show');
        Route::post('/{coinRequest}/approve', [CoinRequestController::class, 'approve'])->name('approve');
        Route::post('/{coinRequest}/reject', [CoinRequestController::class, 'reject'])->name('reject');
        Route::delete('/{coinRequest}', [CoinRequestController::class, 'destroy'])->name('destroy');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{report}', [ReportController::class, 'show'])->name('show');
        Route::put('/{report}/status', [ReportController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy');
    });

    // Files
    Route::prefix('files')->name('files.')->controller(FileController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/list', 'list')->name('list');
        Route::post('/folder', 'createFolder')->name('create-folder');
        Route::post('/upload', 'upload')->name('upload');
        Route::post('/upload-filepond', 'uploadFromFilePond')->name('upload-filepond');
        Route::delete('/', 'delete')->name('delete');
        Route::put('/rename', 'rename')->name('rename');
        Route::post('/extract', 'extract')->name('extract');
        Route::get('/download', 'download')->name('download');
        Route::get('/url', 'getUrl')->name('url');
        Route::post('/move', 'move')->name('move');
        Route::post('/copy', 'copy')->name('copy');

    });

    Route::prefix('upload')->name('upload.')->controller(UploadController::class)->group(function () {
        Route::post('/upload-image', 'uploadImage')->name('upload-image');
        Route::post('/temp-delete', 'tempDelete')->name('temp-delete');
    });

    Route::prefix('config')->name('config.settings.')->controller(SettingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{type}/load-data', 'loadData')->name('load');
        Route::post('/{type}/update', 'update')->name('update');
        Route::get('/{key}', 'file')->name('file');
        Route::post('update-appearance', 'updateAppearance')->name('update-appearance');
    });

    Route::prefix('crawl')->name('crawl.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('admin/crawl/Index');
        })->name('index');
        Route::get('/create', function () {
            return Inertia::render('admin/crawl/Create');
        })->name('create');
    });
});
