<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\MangaCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MangaController extends Controller
{
    /**
     * Get list of mangas with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Manga::with(['author:id,name', 'badge:id,name', 'categories:id,name,slug'])
            ->select(['id', 'manga_author_id', 'manga_badge_id', 'avatar', 'name', 'slug', 'description', 'status', 'total_views', 'rating', 'total_follow', 'created_at', 'updated_at']);

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('manga_categories.slug', $request->category)
                    ->orWhere('manga_categories.id', $request->category);
            });
        }

        // Filter by author
        if ($request->has('author') && $request->author) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('manga_authors.slug', $request->author)
                    ->orWhere('manga_authors.id', $request->author);
            });
        }

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
        $mangas->getCollection()->transform(function ($manga) {
            return $this->transformManga($manga);
        });

        return response()->json([
            'ok' => true,
            'data' => $mangas->items(),
            'pagination' => [
                'current_page' => $mangas->currentPage(),
                'last_page' => $mangas->lastPage(),
                'per_page' => $mangas->perPage(),
                'total' => $mangas->total(),
            ],
        ]);
    }

    /**
     * Get top mangas
     */
    public function top(Request $request): JsonResponse
    {
        $type = $request->query('type', 'all'); // all, daily, weekly, monthly
        $limit = min($request->query('limit', 10), 50);

        $query = Manga::with(['author:id,name', 'badge:id,name', 'categories:id,name,slug'])
            ->select(['id', 'manga_author_id', 'manga_badge_id', 'avatar', 'name', 'slug', 'description', 'status', 'total_views', 'monthly_views', 'weekly_views', 'daily_views', 'rating', 'total_follow', 'created_at']);

        switch ($type) {
            case 'daily':
                $query->orderBy('daily_views', 'desc');
                break;
            case 'weekly':
                $query->orderBy('weekly_views', 'desc');
                break;
            case 'monthly':
                $query->orderBy('monthly_views', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc')->where('rating', '>', 0);
                break;
            case 'follow':
                $query->orderBy('total_follow', 'desc');
                break;
            default:
                $query->orderBy('total_views', 'desc');
        }

        $mangas = $query->limit($limit)->get();

        return response()->json([
            'ok' => true,
            'data' => $mangas->map(fn ($manga) => $this->transformManga($manga)),
        ]);
    }

    /**
     * Get featured mangas
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 10), 50);

        $mangas = Manga::with(['author:id,name', 'badge:id,name', 'categories:id,name,slug'])
            ->whereHas('badge', function ($q) {
                $q->where('name', 'like', '%nổi bật%')
                    ->orWhere('name', 'like', '%featured%');
            })
            ->orWhere('total_follow', '>', 100)
            ->select(['id', 'manga_author_id', 'manga_badge_id', 'avatar', 'name', 'slug', 'description', 'status', 'total_views', 'rating', 'total_follow', 'created_at'])
            ->orderBy('total_follow', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $mangas->map(fn ($manga) => $this->transformManga($manga)),
        ]);
    }

    /**
     * Get new/updated mangas
     */
    public function new(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 20), 50);

        $mangas = Manga::with(['author:id,name', 'badge:id,name', 'categories:id,name,slug'])
            ->select(['id', 'manga_author_id', 'manga_badge_id', 'avatar', 'name', 'slug', 'description', 'status', 'total_views', 'rating', 'total_follow', 'created_at', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $mangas->map(fn ($manga) => $this->transformManga($manga)),
        ]);
    }

    /**
     * Get hot mangas
     */
    public function hot(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 20), 50);

        $mangas = Manga::with(['author:id,name', 'badge:id,name', 'categories:id,name,slug'])
            ->select(['id', 'manga_author_id', 'manga_badge_id', 'avatar', 'name', 'slug', 'description', 'status', 'total_views', 'weekly_views', 'daily_views', 'rating', 'total_follow', 'created_at'])
            ->where('weekly_views', '>', 0)
            ->orderBy('weekly_views', 'desc')
            ->orderBy('daily_views', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $mangas->map(fn ($manga) => $this->transformManga($manga)),
        ]);
    }

    /**
     * Get completed mangas
     */
    public function completed(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 20), 50);

        $mangas = Manga::with(['author:id,name', 'badge:id,name', 'categories:id,name,slug'])
            ->where('status', 'completed')
            ->select(['id', 'manga_author_id', 'manga_badge_id', 'avatar', 'name', 'slug', 'description', 'status', 'total_views', 'rating', 'total_follow', 'created_at'])
            ->orderBy('total_views', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $mangas->map(fn ($manga) => $this->transformManga($manga)),
        ]);
    }

    /**
     * Get manga detail
     */
    public function show(string $slug): JsonResponse
    {
        $manga = Manga::with(['author:id,name,slug', 'badge:id,name', 'categories:id,name,slug', 'chapters' => function ($q) {
            $q->select(['id', 'manga_id', 'name', 'slug', 'order', 'created_at'])->orderBy('order');
        }])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment views
        $manga->increment('total_views');
        $manga->increment('daily_views');
        $manga->increment('weekly_views');
        $manga->increment('monthly_views');

        return response()->json([
            'ok' => true,
            'data' => $this->transformMangaDetail($manga),
        ]);
    }

    /**
     * Transform manga for list
     */
    public function transformManga(Manga $manga): array
    {
        $storageUrl = config('app.storage_url') ?: (config('app.url').'/storage');
        // Remove double slashes
        $storageUrl = rtrim($storageUrl, '/');

        return [
            'id' => $manga->id,
            'name' => $manga->name,
            'slug' => $manga->slug,
            'avatar' => $manga->avatar ? ($config.'/'.$manga->avatar) : null,
            'description' => $manga->description,
            'status' => $manga->status,
            'views' => $manga->total_views,
            'rating' => $manga->rating ?? 0,
            'total_ratings' => $manga->total_ratings ?? 0,
            'follows' => $manga->total_follow ?? 0,
            'author' => $manga->author ? [
                'id' => $manga->author->id,
                'name' => $manga->author->name,
            ] : null,
            'badge' => $manga->badge ? [
                'id' => $manga->badge->id,
                'name' => $manga->badge->name,
            ] : null,
            'categories' => $manga->categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ]),
            'created_at' => $manga->created_at?->toISOString(),
            'updated_at' => $manga->updated_at?->toISOString(),
        ];
    }

    /**
     * Transform manga for detail
     */
    private function transformMangaDetail(Manga $manga): array
    {
        $data = $this->transformManga($manga);
        $data['chapters'] = $manga->chapters->map(fn ($chapter) => [
            'id' => $chapter->id,
            'name' => $chapter->name,
            'slug' => $chapter->slug,
            'order' => $chapter->order,
            'created_at' => $chapter->created_at?->toISOString(),
        ]);

        return $data;
    }
}
