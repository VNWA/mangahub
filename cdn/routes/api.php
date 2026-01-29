<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MangaController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ReadingHistoryController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/', function () {
        return response()->json([
            'ok' => true,
            'message' => 'Welcome to the API',
            'version' => '1.0',
        ]);
    });

    Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
        // Ensure user is authenticated via Sanctum
        $user = $request->user();

        if (! $user) {
            \Log::warning('Broadcasting auth: No user found', [
                'has_bearer_token' => $request->bearerToken() !== null,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated',
            ], 403);
        }

        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        \Log::info('Broadcasting auth: User authenticated', [
            'user_id' => $user->id,
            'channel_name' => $channelName,
            'socket_id' => $socketId,
        ]);

        // Set the authenticated user for channel authorization
        // This is critical: Broadcast::auth() needs the user to be set in the auth context
        auth()->setUser($user);
        auth()->guard('web')->setUser($user);

        // Manually authorize the channel to ensure user is passed correctly
        try {
            // Use Broadcast::auth() which will call channel authorization callbacks
            // The user should now be available in the callbacks
            $response = Broadcast::auth($request);

            \Log::info('Broadcasting auth: Success', [
                'user_id' => $user->id,
                'channel_name' => $channelName,
            ]);

            return $response;
        } catch (\Exception $e) {
            \Log::error('Broadcasting auth: Error', [
                'user_id' => $user->id,
                'channel_name' => $channelName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Authorization failed: '.$e->getMessage(),
            ], 403);
        }
    })->middleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, 'auth:sanctum')->name('broadcasting.auth');

    // Authentication routes
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('guest-login', [AuthController::class, 'guestLogin'])->name('guest.login');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');

    // Social authentication routes
    Route::get('auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('auth/discord', [AuthController::class, 'redirectToDiscord'])->name('auth.discord');
    Route::get('auth/discord/callback', [AuthController::class, 'handleDiscordCallback'])->name('auth.discord.callback');

    // Manga routes
    Route::prefix('mangas')->group(function () {
        Route::get('/', [MangaController::class, 'index'])->name('mangas.index');
        Route::get('/top', [MangaController::class, 'top'])->name('mangas.top');
        Route::get('/featured', [MangaController::class, 'featured'])->name('mangas.featured');
        Route::get('/new', [MangaController::class, 'new'])->name('mangas.new');
        Route::get('/hot', [MangaController::class, 'hot'])->name('mangas.hot');
        Route::get('/completed', [MangaController::class, 'completed'])->name('mangas.completed');
        Route::get('/{slug}', [MangaController::class, 'show'])->name('mangas.show');
    });

    // Chapter routes
    Route::prefix('mangas/{mangaSlug}/chapters')->group(function () {
        Route::get('/', [ChapterController::class, 'index'])->name('chapters.index');
        Route::get('/{chapterSlug}', [ChapterController::class, 'show'])->name('chapters.show');
    });

    // Category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/{slug}', [CategoryController::class, 'show'])->name('categories.show');
    });

    // Search routes
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Comments routes (public read, protected write)
    Route::prefix('comments')->group(function () {
        Route::get('/', [CommentController::class, 'index'])->name('comments.index');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [CommentController::class, 'store'])->name('comments.store');
            Route::put('/{comment}', [CommentController::class, 'update'])->name('comments.update');
            Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
            Route::post('/{comment}/react', [CommentController::class, 'react'])->name('comments.react');
        });
    });

    // Rating routes
    Route::prefix('mangas/{mangaId}/rating')->group(function () {
        Route::get('/', [RatingController::class, 'show'])->name('ratings.show');
        Route::get('/list', [RatingController::class, 'index'])->name('ratings.index');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [RatingController::class, 'store'])->name('ratings.store');
            Route::delete('/', [RatingController::class, 'destroy'])->name('ratings.destroy');
        });
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user'])->name('user');
        Route::post('sync-data', [AuthController::class, 'syncData'])->name('sync.data');
        Route::post('devices/disconnect', [AuthController::class, 'deviceDisconnect'])->name('devices.disconnect');

        // Favorites
        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoriteController::class, 'index'])->name('favorites.index');
            Route::post('/', [FavoriteController::class, 'store'])->name('favorites.store');
            Route::delete('/{mangaId}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
            Route::get('/check/{mangaId}', [FavoriteController::class, 'check'])->name('favorites.check');
        });

        // Reading History
        Route::prefix('reading-history')->group(function () {
            Route::get('/', [ReadingHistoryController::class, 'index'])->name('reading-history.index');
            Route::post('/', [ReadingHistoryController::class, 'store'])->name('reading-history.store');
            Route::delete('/', [ReadingHistoryController::class, 'destroy'])->name('reading-history.destroy');
        });
    });
});
