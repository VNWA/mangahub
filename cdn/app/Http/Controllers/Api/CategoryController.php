<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MangaCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories
     */
    public function index(): JsonResponse
    {
        $categories = MangaCategory::select(['id', 'name', 'slug', 'description', 'created_at'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'manga_count' => $category->mangas()->count(),
            ]),
        ]);
    }

    /**
     * Get category detail with mangas
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $category = MangaCategory::where('slug', $slug)->firstOrFail();

        $query = $category->mangas()
            ->with(['author:id,name', 'badge:id,name', 'categories:id,name,slug'])
            ->select(['id', 'manga_author_id', 'manga_badge_id', 'avatar', 'name', 'slug', 'description', 'status', 'total_views', 'rating', 'total_follow', 'created_at']);

        // Sorting
        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');

        $allowedSorts = ['created_at', 'updated_at', 'total_views', 'rating', 'total_follow', 'name'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->query('per_page', 20), 100);
        $mangas = $query->paginate($perPage);

        // Transform data
        $mangaController = new \App\Http\Controllers\Api\MangaController();
        $mangas->getCollection()->transform(function ($manga) use ($mangaController) {
            return $mangaController->transformManga($manga);
        });

        return response()->json([
            'ok' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
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
