<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MangaAuthor extends Model
{
    use HasFactory;

    protected $fillable = [
        'avatar',
        'name',
        'slug',
        'description',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($author) {
            if (empty($author->slug)) {
                $slug = Str::slug($author->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $author->slug = $uniqueSlug;
            }
        });

        static::updating(function ($author) {
            if ($author->isDirty('name') && empty($author->slug)) {
                $slug = Str::slug($author->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->where('id', '!=', $author->id)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $author->slug = $uniqueSlug;
            }
        });
    }

    public function mangas(): HasMany
    {
        return $this->hasMany(Manga::class, 'manga_author_id');
    }
}
