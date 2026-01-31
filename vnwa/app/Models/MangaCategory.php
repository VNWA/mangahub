<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class MangaCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'avatar',
        'icon',
        'name',
        'slug',
        'description',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $slug = Str::slug($category->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $category->slug = $uniqueSlug;
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $slug = Str::slug($category->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->where('id', '!=', $category->id)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $category->slug = $uniqueSlug;
            }
        });
    }

    public function mangas(): BelongsToMany
    {
        return $this->belongsToMany(Manga::class, 'manga_category_manga');
    }
}
