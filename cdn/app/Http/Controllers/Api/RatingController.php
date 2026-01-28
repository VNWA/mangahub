<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\MangaRating;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RatingController extends Controller
{
    /**
     * Get rating for a manga
     */
    public function show(Request $request, int $mangaId): JsonResponse
    {
        $manga = Manga::findOrFail($mangaId);
        $page = Page::getOrCreateFor($manga);
        $user = $request->user();

        $cacheKey = "rating:manga:{$mangaId}";
        $data = Cache::remember($cacheKey, 600, function () use ($page) {
            $ratings = MangaRating::where('page_id', $page->id)
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->get()
                ->keyBy('rating');

            return [
                'average_rating' => round($page->rating, 2),
                'total_ratings' => $page->total_ratings,
                'rating_distribution' => [
                    '5' => $ratings->get(5)?->count ?? 0,
                    '4' => $ratings->get(4)?->count ?? 0,
                    '3' => $ratings->get(3)?->count ?? 0,
                    '2' => $ratings->get(2)?->count ?? 0,
                    '1' => $ratings->get(1)?->count ?? 0,
                ],
            ];
        });

        $userRating = null;
        if ($user) {
            $userRating = MangaRating::where('page_id', $page->id)
                ->where('user_id', $user->id)
                ->first();
        }

        return response()->json([
            'ok' => true,
            'data' => array_merge($data, [
                'user_rating' => $userRating ? [
                    'rating' => $userRating->rating,
                    'review' => $userRating->review,
                    'created_at' => $userRating->created_at->toISOString(),
                ] : null,
            ]),
        ]);
    }

    /**
     * Submit or update rating
     */
    public function store(Request $request, int $mangaId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $manga = Manga::findOrFail($mangaId);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
        ]);

        $page = Page::getOrCreateFor($manga);

        DB::transaction(function () use ($page, $user, $request, $manga) {
            $rating = MangaRating::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'page_id' => $page->id,
                ],
                [
                    'rating' => $request->rating,
                    'review' => $request->review,
                ]
            );

            // Update page rating
            $page->updateRating();

            // Update manga average rating (for backward compatibility)
            $manga->update([
                'rating' => $page->rating,
                'total_ratings' => $page->total_ratings,
            ]);
        });

        Cache::forget("rating:manga:{$manga->id}");

        return response()->json([
            'ok' => true,
            'message' => 'Rating submitted successfully',
        ]);
    }

    /**
     * Delete rating
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

        $manga = Manga::findOrFail($mangaId);

        $page = Page::getOrCreateFor($manga);

        DB::transaction(function () use ($page, $user, $manga) {
            MangaRating::where('page_id', $page->id)
                ->where('user_id', $user->id)
                ->delete();

            // Update page rating
            $page->updateRating();

            // Update manga average rating (for backward compatibility)
            $manga->update([
                'rating' => $page->rating,
                'total_ratings' => $page->total_ratings,
            ]);
        });

        Cache::forget("rating:manga:{$manga->id}");

        return response()->json([
            'ok' => true,
            'message' => 'Rating deleted successfully',
        ]);
    }
}
