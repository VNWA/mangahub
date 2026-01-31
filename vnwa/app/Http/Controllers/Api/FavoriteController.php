<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Manga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Get user favorites
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $favorites = Favorite::where('user_id', $user->id)
            ->with(['manga.author:id,name', 'manga.badge:id,name', 'manga.categories:id,name,slug'])
            ->orderBy('created_at', 'desc')
            ->get();

        $mangaController = new MangaController();
        $data = $favorites->map(function ($favorite) use ($mangaController) {
            return $mangaController->transformManga($favorite->manga);
        });

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    /**
     * Add to favorites
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'manga_id' => 'required|exists:mangas,id',
        ]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => $user->id,
            'manga_id' => $request->manga_id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Đã thêm vào yêu thích',
            'data' => $favorite,
        ]);
    }

    /**
     * Remove from favorites
     */
    public function destroy(Request $request, int $mangaId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $favorite = Favorite::where('user_id', $user->id)
            ->where('manga_id', $mangaId)
            ->first();

        if ($favorite) {
            $favorite->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Đã xóa khỏi yêu thích',
        ]);
    }

    /**
     * Check if manga is favorited
     */
    public function check(Request $request, int $mangaId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'ok' => true,
                'is_favorited' => false,
            ]);
        }

        $isFavorited = Favorite::where('user_id', $user->id)
            ->where('manga_id', $mangaId)
            ->exists();

        return response()->json([
            'ok' => true,
            'is_favorited' => $isFavorited,
        ]);
    }
}
