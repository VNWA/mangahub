<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Storage;

class Manga extends Model
{
    use HasFactory;

    protected $fillable = [
        'manga_author_id',
        'manga_badge_id',
        'user_id',
        'avatar',
        'name',
        'slug',
        'description',
        'status',
        'total_views',
        'monthly_views',
        'weekly_views',
        'daily_views',
        'total_follow',
        'rating',
        'total_ratings',
    ];

    protected function casts(): array
    {
        return [
            'total_views' => 'integer',
            'monthly_views' => 'integer',
            'weekly_views' => 'integer',
            'daily_views' => 'integer',
            'total_follow' => 'integer',
            'rating' => 'float',
            'total_ratings' => 'integer',
        ];
    }
    protected $appends = ['avatar_url'];
    public function getAvatarUrlAttribute()
    {
        if (! $this->avatar) {
            return url('vnwa/no-image.jpg');
        }
        if(str_starts_with($this->avatar, 'http') || str_starts_with($this->avatar, 'https')){
            return $this->avatar;
        }

        return Storage::url($this->avatar);
    }
    public function author(): BelongsTo
    {
        return $this->belongsTo(MangaAuthor::class, 'manga_author_id');
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(MangaBadge::class, 'manga_badge_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(MangaChapter::class)->orderBy('order');
    }
public function latestChapters()
{
    return $this->hasMany(MangaChapter::class)
        ->orderByDesc('order');
}
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(MangaCategory::class, 'manga_category_manga');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function readingHistory()
    {
        return $this->hasMany(ReadingHistory::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id')->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
    }

    public function allComments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function ratings()
    {
        return $this->hasMany(MangaRating::class);
    }

    public function userRating()
    {
        return $this->hasOne(MangaRating::class)->where('user_id', auth()->id());
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($manga) {
            if (empty($manga->slug)) {
                $slug = Str::slug($manga->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $manga->slug = $uniqueSlug;
            }
        });

        static::updating(function ($manga) {
            if ($manga->isDirty('name') && empty($manga->slug)) {
                $slug = Str::slug($manga->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->where('id', '!=', $manga->id)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $manga->slug = $uniqueSlug;
            }
        });
    }
}
