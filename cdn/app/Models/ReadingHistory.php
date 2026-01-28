<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'manga_id',
        'manga_chapter_id',
        'chapter_order',
        'chapter_name',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'chapter_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(MangaChapter::class, 'manga_chapter_id');
    }
}
