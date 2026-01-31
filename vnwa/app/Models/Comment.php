<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'page_id',
        'parent_id',
        'root_id',
        'depth',
        'content',
        'likes_count',
        'dislikes_count',
        'replies_count',
        'is_edited',
        'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
            'dislikes_count' => 'integer',
            'replies_count' => 'integer',
            'depth' => 'integer',
            'is_edited' => 'boolean',
            'is_pinned' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function root(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'root_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function userReaction()
    {
        return $this->hasOne(CommentReaction::class)->where('user_id', auth()->id());
    }

    /**
     * Increment replies count when a reply is added
     */
    public function incrementRepliesCount(): void
    {
        $this->increment('replies_count');
    }

    /**
     * Decrement replies count when a reply is deleted
     */
    public function decrementRepliesCount(): void
    {
        $this->decrement('replies_count');
    }
}
