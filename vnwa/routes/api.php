<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\CoinController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MangaController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ReadingHistoryController;
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

            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated',
            ], 403);
        }

        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        // Set the authenticated user for channel authorization
        // This is critical: Broadcast::auth() needs the user to be set in the auth context
        auth()->setUser($user);
        auth()->guard('web')->setUser($user);

        // Manually authorize the channel to ensure user is passed correctly
        try {
            // Use Broadcast::auth() which will call channel authorization callbacks
            // The user should now be available in the callbacks
            $response = Broadcast::auth($request);

            return $response;
        } catch (\Exception $e) {

            return response()->json([
                'ok' => false,
                'message' => 'Authorization failed: '.$e->getMessage(),
            ], 403);
        }
    })->middleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, 'auth:sanctum');

    // Authentication routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('guest-login', [AuthController::class, 'guestLogin']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Social authentication routes
    Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
    Route::get('auth/discord', [AuthController::class, 'redirectToDiscord']);
    Route::get('auth/discord/callback', [AuthController::class, 'handleDiscordCallback']);

    // Search route (must be before mangas/{slug} to avoid conflict)
    Route::get('/search', [MangaController::class, 'search']);

    // Manga routes
    Route::prefix('mangas')->group(function () {
        Route::get('/', [MangaController::class, 'index']);
        Route::get('/top', [MangaController::class, 'top']);
        Route::get('/featured', [MangaController::class, 'featured']);
        Route::get('/new', [MangaController::class, 'new']);
        Route::get('/hot', [MangaController::class, 'hot']);
        Route::get('/completed', [MangaController::class, 'completed']);
        Route::get('/{slug}', [MangaController::class, 'show']);
    });

    // Chapter routes
    Route::prefix('mangas/{mangaSlug}/chapters')->group(function () {
        Route::get('/', [ChapterController::class, 'index']);
        Route::get('/{chapterSlug}', [ChapterController::class, 'show']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/{chapterSlug}/report', [ChapterController::class, 'report']);
        });
    });

    // Category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{slug}', [CategoryController::class, 'show']);
    });
    Route::prefix('badges')->controller(BadgeController::class)->group(function () {
        Route::get('/', 'index');
    });

    // Comments routes (public read, protected write)
    Route::prefix('comments')->group(function () {
        Route::get('/', [CommentController::class, 'index']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [CommentController::class, 'store']);
            Route::put('/{comment}', [CommentController::class, 'update']);
            Route::delete('/{comment}', [CommentController::class, 'destroy']);
            Route::post('/{comment}/react', [CommentController::class, 'react']);
        });
    });

    // Rating routes
    Route::prefix('mangas/{mangaId}/rating')->group(function () {
        Route::get('/', [RatingController::class, 'show']);
        Route::get('/list', [RatingController::class, 'index']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [RatingController::class, 'store']);
            Route::delete('/', [RatingController::class, 'destroy']);
        });
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('sync-data', [AuthController::class, 'syncData']);
        Route::post('devices/disconnect', [AuthController::class, 'deviceDisconnect']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('settings', [AuthController::class, 'updateSettings']);

        // Favorites
        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoriteController::class, 'index']);
            Route::post('/', [FavoriteController::class, 'store']);
            Route::delete('/{mangaId}', [FavoriteController::class, 'destroy']);
            Route::get('/check/{mangaId}', [FavoriteController::class, 'check']);
        });

        // Reading History
        Route::prefix('reading-history')->group(function () {
            Route::get('/', [ReadingHistoryController::class, 'index']);
            Route::post('/', [ReadingHistoryController::class, 'store']);
            Route::delete('/', [ReadingHistoryController::class, 'destroy']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        });

        // Coin routes
        Route::prefix('coins')->group(function () {
            Route::get('/balance', [CoinController::class, 'balance']);
            Route::post('/deposit', [CoinController::class, 'deposit']);
            Route::get('/transactions', [CoinController::class, 'transactions']);
            Route::post('/unlock-chapter', [CoinController::class, 'unlockChapter']);
            Route::get('/unlock-history', [CoinController::class, 'unlockHistory']);
        });
    });
});
