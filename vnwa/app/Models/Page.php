<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'pageable_id',
        'pageable_type',
        'views_count',
        'rating',
        'total_ratings',
        'comments_count',
    ];

    protected function casts(): array
    {
        return [
            'views_count' => 'integer',
            'rating' => 'float',
            'total_ratings' => 'integer',
            'comments_count' => 'integer',
        ];
    }

    public function pageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(MangaRating::class);
    }

    /**
     * Get or create page for a pageable model
     */
    public static function getOrCreateFor($pageable): self
    {
        $cacheKey = "page:{$pageable->getMorphClass()}:{$pageable->id}";

        return Cache::remember($cacheKey, 3600, function () use ($pageable) {
            return static::firstOrCreate(
                [
                    'pageable_id' => $pageable->id,
                    'pageable_type' => $pageable->getMorphClass(),
                ],
                [
                    'views_count' => 0,
                    'rating' => 0,
                    'total_ratings' => 0,
                    'comments_count' => 0,
                ]
            );
        });
    }

    /**
     * Increment views count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
        $this->clearCache();
    }

    /**
     * Update comments count
     */
    public function updateCommentsCount(): void
    {
        $this->comments_count = $this->allComments()->count();
        $this->save();
        $this->clearCache();
    }

    /**
     * Update rating
     */
    public function updateRating(): void
    {
        $ratings = $this->ratings();
        $this->total_ratings = $ratings->count();
        $this->rating = $ratings->avg('rating') ?? 0;
        $this->save();
        $this->clearCache();
    }

    /**
     * Clear cache for this page
     */
    protected function clearCache(): void
    {
        Cache::forget("page:{$this->pageable_type}:{$this->pageable_id}");
    }
}
