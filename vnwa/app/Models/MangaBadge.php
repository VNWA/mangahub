<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MangaBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'light_text_color',
        'light_bg_color',
        'dark_text_color',
        'dark_bg_color',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($badge) {
            if (empty($badge->slug)) {
                $slug = Str::slug($badge->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $badge->slug = $uniqueSlug;
            }
        });

        static::updating(function ($badge) {
            if ($badge->isDirty('name') && empty($badge->slug)) {
                $slug = Str::slug($badge->name);
                $uniqueSlug = $slug;
                $counter = 1;
                while (static::where('slug', $uniqueSlug)->where('id', '!=', $badge->id)->exists()) {
                    $uniqueSlug = $slug.'-'.$counter;
                    $counter++;
                }
                $badge->slug = $uniqueSlug;
            }
        });
    }

    public function mangas(): HasMany
    {
        return $this->hasMany(Manga::class, 'manga_badge_id');
    }
}
