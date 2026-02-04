<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MangaController extends Controller
{
    /**
     * Get list of mangas with filters
     */
  public function index(Request $request): JsonResponse
{
    $perPage = min((int) $request->query('limit', 20), 100);

    $query = Manga::query()
        ->select([
            'id',
            'manga_badge_id',
            'avatar',
            'name',
            'slug',
            'status',
            'total_views',
            'rating',
            'total_ratings',
            'total_follow',
            'created_at',
            'updated_at',
        ])
        ->with([
            'badge:id,name,slug,light_text_color,light_bg_color,dark_text_color,dark_bg_color',
            'categories:id,name,slug',
            'chapters:id,manga_id,name,slug,order,created_at',
        ]);

    /* -------- SEARCH -------- */
    if ($search = $request->query('search')) {
        $query->where('name', 'like', "%$search%")->orWhere('slug', 'like', "%$search%");
    }

    /* -------- FILTER -------- */
    $query
        ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
        ->when($request->query('badge_id'), fn ($q, $v) => $q->where('manga_badge_id', $v));

    /* -------- CATEGORY FILTER (TỐI ƯU) -------- */
    if ($request->filled('category_ids')) {
        $ids = explode(',', $request->query('category_ids'));

        $query->whereExists(function ($q) use ($ids) {
            $q->selectRaw(1)
              ->from('manga_category')
              ->whereColumn('manga_category.manga_id', 'mangas.id')
              ->whereIn('manga_category.category_id', $ids);
        });
    }

    /* -------- SORT -------- */
    $sortMap = [
        'created_at_asc'  => ['created_at', 'asc'],
        'created_at_desc' => ['created_at', 'desc'],
        'rating_asc'      => ['rating', 'asc'],
        'rating_desc'     => ['rating', 'desc'],
        'views_asc'       => ['total_views', 'asc'],
        'views_desc'      => ['total_views', 'desc'],
        'follows_asc'     => ['total_follow', 'asc'],
        'follows_desc'    => ['total_follow', 'desc'],
        'name_asc'        => ['name', 'asc'],
        'name_desc'       => ['name', 'desc'],
    ];

    [$col, $dir] = $sortMap[$request->query('sort')] ?? ['created_at', 'desc'];
    $query->orderBy($col, $dir);

    /* -------- PAGINATE -------- */
    $mangas = $query->paginate($perPage);

    /* -------- TRANSFORM NHẸ -------- */
    $data = $mangas->getCollection()->map(function (Manga $manga) {
        return [
            'id' => $manga->id,
            'name' => $manga->name,
            'slug' => $manga->slug,
            'avatar' => $manga->avatar ?? '',
            'views' => $manga->total_views,
            'rating' => $manga->rating ?? 0,
            'total_ratings' => $manga->total_ratings ?? 0,
            'follows' => $manga->total_follow ?? 0,

            'badge' => $manga->badge ? [
                'id' => $manga->badge->id,
                'name' => $manga->badge->name,
                'slug' => $manga->badge->slug,
                'light_text_color' => $manga->badge->light_text_color,
                'light_bg_color' => $manga->badge->light_bg_color,
                'dark_text_color' => $manga->badge->dark_text_color,
                'dark_bg_color' => $manga->badge->dark_bg_color,
            ] : null,

            'categories' => $manga->categories->map(fn ($c) => [
                'name' => $c->name,
                'slug' => $c->slug,
            ]),

            'chapters' => $manga->chapters
                ->sortByDesc('order')
                ->take(3)
                ->values()
                ->map(fn ($c) => [
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'created_at' => $c->created_at?->toISOString(),
                ]),

            'created_at' => $manga->created_at?->toISOString(),
            'updated_at' => $manga->updated_at?->toISOString(),
        ];
    });

    return response()->json([
        'ok' => true,
        'data' => $data,
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

        $query = $this->baseMangaListQuery()
            ->select(['id', 'manga_badge_id', 'avatar', 'name', 'slug', 'status', 'rating', 'monthly_views', 'weekly_views', 'daily_views', 'total_views', 'total_follow']);

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
        $mangas->transform(fn (Manga $manga) => $this->transformMangaList($manga));

        return response()->json([
            'ok' => true,
            'data' => $mangas->values(),
        ]);
    }

    /**
     * Get featured mangas
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 10), 50);

        $mangas = $this->baseMangaListQuery()
            ->whereHas('badge', function ($q) {
                $q->where('name', 'like', '%nổi bật%')
                    ->orWhere('name', 'like', '%featured%');
            })
            ->orWhere('total_follow', '>', 100)
            ->select(['id', 'manga_badge_id', 'avatar', 'name', 'slug', 'status', 'rating'])
            ->orderBy('total_follow', 'desc')
            ->limit($limit)
            ->get();

        $mangas->transform(fn (Manga $manga) => $this->transformMangaList($manga));

        return response()->json([
            'ok' => true,
            'data' => $mangas->values(),
        ]);
    }

    /**
     * Get new/updated mangas
     */
    public function new(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 20), 50);

        $mangas = $this->baseMangaListQuery()
            ->select(['id', 'manga_badge_id', 'avatar', 'name', 'slug', 'status', 'rating', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        $mangas->transform(fn (Manga $manga) => $this->transformMangaList($manga));

        return response()->json([
            'ok' => true,
            'data' => $mangas->values(),
        ]);
    }

    /**
     * Get hot mangas
     */
    public function hot(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 20), 50);

        $mangas = $this->baseMangaListQuery()
            ->select(['id', 'manga_badge_id', 'avatar', 'name', 'slug', 'status', 'rating', 'weekly_views', 'daily_views'])
            ->where('weekly_views', '>', 0)
            ->orderBy('weekly_views', 'desc')
            ->orderBy('daily_views', 'desc')
            ->limit($limit)
            ->get();

        $mangas->transform(fn (Manga $manga) => $this->transformMangaList($manga));

        return response()->json([
            'ok' => true,
            'data' => $mangas->values(),
        ]);
    }

    /**
     * Get completed mangas
     */
    public function completed(Request $request): JsonResponse
    {
        $limit = min($request->query('limit', 20), 50);

        $mangas = $this->baseMangaListQuery()
            ->where('status', 'completed')
            ->select(['id', 'manga_badge_id', 'avatar', 'name', 'slug', 'status', 'rating'])
            ->orderBy('total_views', 'desc')
            ->limit($limit)
            ->get();

        $mangas->transform(fn (Manga $manga) => $this->transformMangaList($manga));

        return response()->json([
            'ok' => true,
            'data' => $mangas->values(),
        ]);
    }

    /**
     * Get manga detail
     */
    public function show(string $slug): JsonResponse
    {
        $manga = $this->baseMangaQuery()
            ->with(['chapters' => function ($q) {
                $q->select(['id', 'manga_id', 'name', 'slug', 'order', 'created_at'])->orderBy('order');
            }])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment views in a single query
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
     * Base query with common eager loading
     */
    private function baseMangaQuery()
    {
        return Manga::with([
            'author:id,name',
            'badge:id,name,slug,light_text_color,light_bg_color,dark_text_color,dark_bg_color',
            'categories:id,name,slug',
        ]);
    }

    /**
     * Base query for list views (with latest chapters)
     */
private function baseMangaListQuery()
{
    return Manga::with([
        'badge:id,name,slug,light_text_color,light_bg_color,dark_text_color,dark_bg_color',
        'latestChapters' => function ($q) {
            $q->select(['id', 'manga_id', 'name', 'slug', 'order', 'created_at'])
              ->limit(3); // 3 chapter mới nhất
        },
    ]);
}



    /**
     * Transform manga for list (minimal data for list views)
     */
    private function transformMangaList(Manga $manga): array
    {
        return [
            'name' => $manga->name,
            'slug' => $manga->slug,
            'status' => $manga->status,
            'avatar' => $manga->avatar ?: '',
            'rating' => $manga->rating ?? 0,
            'badge' => $manga->badge ? [
                'id' => $manga->badge->id,
                'name' => $manga->badge->name,
                'slug' => $manga->badge->slug,
                'light_text_color' => $manga->badge->light_text_color,
                'light_bg_color' => $manga->badge->light_bg_color,
                'dark_text_color' => $manga->badge->dark_text_color,
                'dark_bg_color' => $manga->badge->dark_bg_color,
            ] : null,
          'chapters' => $manga->latestChapters->map(fn ($c) => [
    'name' => $c->name,
    'slug' => $c->slug,
    'order' => $c->order,
    'created_at' => $c->created_at?->toISOString(),
]),
        ];
    }

    /**
     * Transform manga for list (full data for index/search)
     */
    private function transformManga(Manga $manga): array
    {
        return [
            'id' => $manga->id,
            'name' => $manga->name,
            'slug' => $manga->slug,
            'avatar' => $manga->avatar ?: '',
            'description' => $manga->description,
            'status' => $manga->status,
            'views' => $manga->total_views,
            'rating' => $manga->rating ?? 0,
            'total_ratings' => $manga->total_ratings ?? 0,
            'follows' => $manga->total_follow ?? 0,
            'author' => $manga->author ? [
                'name' => $manga->author->name,
            ] : null,
            'badge' => $manga->badge ? [
                'id' => $manga->badge->id,
                'name' => $manga->badge->name,
                'slug' => $manga->badge->slug,
                'light_text_color' => $manga->badge->light_text_color,
                'light_bg_color' => $manga->badge->light_bg_color,
                'dark_text_color' => $manga->badge->dark_text_color,
                'dark_bg_color' => $manga->badge->dark_bg_color,
            ] : null,
            'categories' => $manga->categories->map(fn ($cat) => [
                'name' => $cat->name,
                'slug' => $cat->slug,
            ])->values(),
            'created_at' => $manga->created_at?->toISOString(),
            'updated_at' => $manga->updated_at?->toISOString(),
        ];
    }

    /**
     * Search mangas with filters
     */
    public function search(Request $request): JsonResponse
    {
        $query = $this->baseMangaQuery()
            ->select(['id', 'manga_author_id', 'manga_badge_id', 'avatar', 'name', 'slug', 'description', 'status', 'total_views', 'rating', 'total_follow', 'created_at', 'updated_at']);

        // Search query
        $search = $request->query('q', '');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
                ->orWhereHas('author', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('categories', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
        }

        // Filter by status
        if ($request->has('status') && $request->status && $request->status !== 'Tất cả') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $categories = is_array($request->category) ? $request->category : [$request->category];
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->whereIn('manga_categories.slug', $categories)
                    ->orWhereIn('manga_categories.id', $categories);
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

        // Map Vietnamese sort options
        $sortMap = [
            'Mới nhất' => 'created_at',
            'Hot nhất' => 'total_views',
            'Đánh giá cao' => 'rating',
            'Lượt xem' => 'total_views',
        ];

        if (isset($sortMap[$sortBy])) {
            $sortBy = $sortMap[$sortBy];
        }

        $allowedSorts = ['created_at', 'updated_at', 'total_views', 'rating', 'total_follow', 'name'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->query('per_page', 20), 100);
        $mangas = $query->paginate($perPage);

        // Transform data
        $mangas->getCollection()->transform(fn (Manga $manga) => $this->transformManga($manga));

        return response()->json([
            'ok' => true,
            'query' => $search,
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
     * Transform manga for detail
     */
    private function transformMangaDetail(Manga $manga): array
    {
        $data = $this->transformManga($manga);
        $data['chapters'] = $manga->chapters->sortByDesc('order')->map(fn ($chapter) => [
            'id' => $chapter->id,
            'name' => $chapter->name,
            'slug' => $chapter->slug,
            'order' => $chapter->order,
            'created_at' => $chapter->created_at?->toISOString(),
        ]);
        $page = Page::getOrCreateFor($manga);
        $data['comment_count'] = $page->comments_count ?? 0;

        return $data;
    }
}
