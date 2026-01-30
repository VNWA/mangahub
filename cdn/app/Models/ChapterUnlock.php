<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChapterUnlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'manga_chapter_id',
        'coin_spent',
    ];

    protected function casts(): array
    {
        return [
            'coin_spent' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(MangaChapter::class, 'manga_chapter_id');
    }
}
