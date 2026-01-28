<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerChapterContent extends Model
{
    protected $fillable = [
        'manga_server_id',
        'manga_chapter_id',
        'urls',
    ];

    protected function casts(): array
    {
        return [
            'urls' => 'array',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(MangaServer::class, 'manga_server_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(MangaChapter::class, 'manga_chapter_id');
    }
}
