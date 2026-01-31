<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\MangaChapter;
use App\Models\ReadingHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingHistoryController extends Controller
{
    /**
     * Get user reading history
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

        $history = ReadingHistory::where('user_id', $user->id)
            ->with(['manga.author:id,name', 'manga.badge:id,name', 'manga.categories:id,name,slug', 'chapter:id,name,slug,order'])
            ->orderBy('last_read_at', 'desc')
            ->get();

        $mangaController = new MangaController();
        $data = $history->map(function ($item) use ($mangaController) {
            $manga = $mangaController->transformManga($item->manga);
            return [
                'id' => $item->id,
                'manga' => $manga,
                'chapter' => $item->chapter ? [
                    'id' => $item->chapter->id,
                    'name' => $item->chapter->name,
                    'slug' => $item->chapter->slug,
                    'order' => $item->chapter->order,
                ] : null,
                'chapter_name' => $item->chapter_name,
                'chapter_order' => $item->chapter_order,
                'last_read_at' => $item->last_read_at->toISOString(),
                'created_at' => $item->created_at->toISOString(),
            ];
        });

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    /**
     * Add/Update reading history
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
            'chapter_id' => 'nullable|exists:manga_chapters,id',
            'chapter_order' => 'nullable|integer',
            'chapter_name' => 'nullable|string',
        ]);

        $manga = Manga::findOrFail($request->manga_id);
        
        $chapter = null;
        if ($request->chapter_id) {
            $chapter = MangaChapter::find($request->chapter_id);
        }

        $history = ReadingHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'manga_id' => $manga->id,
            ],
            [
                'manga_chapter_id' => $chapter?->id,
                'chapter_order' => $chapter?->order ?? $request->chapter_order,
                'chapter_name' => $chapter?->name ?? $request->chapter_name,
                'last_read_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Đã cập nhật lịch sử đọc',
            'data' => $history,
        ]);
    }

    /**
     * Clear reading history
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        ReadingHistory::where('user_id', $user->id)->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Đã xóa lịch sử đọc',
        ]);
    }
}
