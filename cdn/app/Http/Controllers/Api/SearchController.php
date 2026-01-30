<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search mangas
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $perPage = min($request->query('per_page', 20), 100);

        if (empty($query)) {
            return response()->json([
                'ok' => true,
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ],
            ]);
        }

        $mangas = Manga::with(['author:id,name', 'badge:id,name', 'categories:id,name,slug'])
            ->select(['id', 'manga_author_id', 'manga_badge_id', 'avatar', 'name', 'slug', 'description', 'status', 'total_views', 'rating', 'total_follow', 'created_at'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orWhereHas('author', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orWhereHas('categories', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orderBy('total_views', 'desc')
            ->paginate($perPage);

        // Transform data
        $mangaController = new \App\Http\Controllers\Api\MangaController;
        $mangas->getCollection()->transform(function ($manga) use ($mangaController) {
            $transformed = $mangaController->transformManga($manga);
            // Ensure title field exists for frontend
            if (! isset($transformed['title']) && isset($transformed['name'])) {
                $transformed['title'] = $transformed['name'];
            }

            return $transformed;
        });

        return response()->json([
            'ok' => true,
            'query' => $query,
            'data' => $mangas->items(),
            'pagination' => [
                'current_page' => $mangas->currentPage(),
                'last_page' => $mangas->lastPage(),
                'per_page' => $mangas->perPage(),
                'total' => $mangas->total(),
            ],
        ]);
    }
}
