<?php

namespace App\Models;

use App\Events\ChapterCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MangaChapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'manga_id',
        'user_id',
        'order',
        'name',
        'slug',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($chapter) {
            if (empty($chapter->slug)) {
                $slug = Str::slug($chapter->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->where('manga_id', $chapter->manga_id)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $chapter->slug = $uniqueSlug;
            }
        });

        static::created(function ($chapter) {
            event(new ChapterCreated($chapter));
        });

        static::updating(function ($chapter) {
            if ($chapter->isDirty('name') && empty($chapter->slug)) {
                $slug = Str::slug($chapter->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->where('manga_id', $chapter->manga_id)->where('id', '!=', $chapter->id)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $chapter->slug = $uniqueSlug;
            }
        });
    }

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serverContents(): HasMany
    {
        return $this->hasMany(ServerChapterContent::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id')->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
    }

    public function allComments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
