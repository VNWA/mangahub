<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\MangaChapter;
use App\Models\Report;
use App\Models\ServerChapterContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    /**
     * Get chapters of a manga
     */
    public function index(Request $request, string $mangaSlug): JsonResponse
    {
        $manga = Manga::where('slug', $mangaSlug)->firstOrFail();

        $user = $request->user();
        $chapters = MangaChapter::where('manga_id', $manga->id)
            ->select(['id', 'manga_id', 'name', 'slug', 'order', 'description', 'coin_cost', 'is_locked', 'created_at', 'updated_at'])
            ->orderBy('order')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $chapters->map(function ($chapter) use ($user) {
                $isUnlocked = $user ? $chapter->isUnlockedBy($user->id) : false;
                $isAccessible = $chapter->isAccessible($user?->id);

                return [
                    'id' => $chapter->id,
                    'name' => $chapter->name,
                    'slug' => $chapter->slug,
                    'order' => $chapter->order,
                    'description' => $chapter->description,
                    'coin_cost' => $chapter->coin_cost,
                    'is_locked' => $chapter->is_locked,
                    'is_unlocked' => $isUnlocked,
                    'is_accessible' => $isAccessible,
                    'created_at' => $chapter->created_at?->toISOString(),
                    'updated_at' => $chapter->updated_at?->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Get chapter detail with content
     */
    public function show(Request $request, string $mangaSlug, string $chapterSlug): JsonResponse
    {
        $user = $request->user();
        $manga = Manga::where('slug', $mangaSlug)->firstOrFail();
        $chapter = MangaChapter::where('manga_id', $manga->id)
            ->where('slug', $chapterSlug)
            ->with(['serverContents.server:id,name'])
            ->firstOrFail();

        // Check if chapter is accessible
        $isUnlocked = $user ? $chapter->isUnlockedBy($user->id) : false;
        $isAccessible = $chapter->isAccessible($user?->id);

        // Get previous and next chapters
        $prevChapter = MangaChapter::where('manga_id', $manga->id)
            ->where('order', '<', $chapter->order)
            ->orderBy('order', 'desc')
            ->first(['id', 'name', 'slug', 'order']);

        $nextChapter = MangaChapter::where('manga_id', $manga->id)
            ->where('order', '>', $chapter->order)
            ->orderBy('order', 'asc')
            ->first(['id', 'name', 'slug', 'order']);

        // Get all available servers
        $availableServers = ServerChapterContent::where('manga_chapter_id', $chapter->id)
            ->with('server:id,name')
            ->get()
            ->map(fn ($sc) => [
                'id' => $sc->manga_server_id,
                'name' => $sc->server->name ?? 'Unknown',
            ])
            ->values();

        // Get content from requested server or first available
        $serverId = $request->query('server_id');
        $serverContent = null;

        if ($serverId) {
            $serverContent = ServerChapterContent::where('manga_chapter_id', $chapter->id)
                ->where('manga_server_id', $serverId)
                ->with('server:id,name')
                ->first();
        }

        // Fallback to first available server if requested server not found
        if (! $serverContent && $availableServers->isNotEmpty()) {
            $serverContent = ServerChapterContent::where('manga_chapter_id', $chapter->id)
                ->where('manga_server_id', $availableServers->first()['id'])
                ->with('server:id,name')
                ->first();
        }

        // Only show content if chapter is accessible
        $content = null;
        if ($isAccessible && $serverContent) {
            $content = [
                'server_id' => $serverContent->manga_server_id,
                'server_name' => $serverContent->server->name ?? null,
                'images' => $serverContent->urls,
                'content' => $serverContent->content,
            ];
        }

        // Increment views only if accessible
        if ($isAccessible) {
            $manga->increment('total_views');
            $manga->increment('daily_views');
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $chapter->id,
                'name' => $chapter->name,
                'slug' => $chapter->slug,
                'order' => $chapter->order,
                'description' => $chapter->description,
                'coin_cost' => $chapter->coin_cost,
                'is_locked' => $chapter->is_locked,
                'is_unlocked' => $isUnlocked,
                'is_accessible' => $isAccessible,
                'manga' => [
                    'id' => $manga->id,
                    'name' => $manga->name,
                    'slug' => $manga->slug,
                ],
                'prev_chapter' => $prevChapter ? [
                    'id' => $prevChapter->id,
                    'name' => $prevChapter->name,
                    'slug' => $prevChapter->slug,
                    'order' => $prevChapter->order,
                ] : null,
                'next_chapter' => $nextChapter ? [
                    'id' => $nextChapter->id,
                    'name' => $nextChapter->name,
                    'slug' => $nextChapter->slug,
                    'order' => $nextChapter->order,
                ] : null,
                'content' => $content,
                'available_servers' => $availableServers,
                'created_at' => $chapter->created_at?->toISOString(),
                'updated_at' => $chapter->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Report a chapter
     */
    public function report(Request $request, string $mangaSlug, string $chapterSlug): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $manga = Manga::where('slug', $mangaSlug)->firstOrFail();
        $chapter = MangaChapter::where('manga_id', $manga->id)
            ->where('slug', $chapterSlug)
            ->firstOrFail();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check if user already reported this chapter
        $existingReport = Report::where('user_id', $user->id)
            ->where('reportable_type', 'MangaChapter')
            ->where('reportable_id', $chapter->id)
            ->where('status', 'pending')
            ->first();

        if ($existingReport) {
            return response()->json([
                'ok' => false,
                'message' => 'Bạn đã báo cáo chapter này rồi. Vui lòng chờ xử lý.',
            ], 422);
        }

        $report = Report::create([
            'user_id' => $user->id,
            'reportable_type' => 'MangaChapter',
            'reportable_id' => $chapter->id,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Báo cáo đã được gửi thành công. Cảm ơn bạn đã phản hồi!',
            'data' => [
                'id' => $report->id,
                'status' => $report->status,
            ],
        ], 201);
    }
}
