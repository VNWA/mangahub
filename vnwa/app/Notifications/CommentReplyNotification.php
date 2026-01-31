<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CommentReplyNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public function __construct(
        public Comment $reply,
        public Comment $parentComment
    ) {}

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        // Reload relationships in case they weren't serialized with the model
        // Use fresh() to reload from database, or loadMissing if already loaded
        if (! $this->reply->relationLoaded('user')) {
            $this->reply->load('user:id,name,avatar');
        }
        if (! $this->reply->relationLoaded('page')) {
            $this->reply->load('page.pageable');
        }

        $page = $this->reply->page;
        $pageable = $page?->pageable;

        $mangaSlug = null;
        $commentId = $this->reply->root_id ?? $this->reply->id; // Use root_id for navigation

        if ($pageable instanceof \App\Models\Manga) {
            $mangaSlug = $pageable->slug;
        } elseif ($pageable instanceof \App\Models\MangaChapter) {
            if (! $pageable->relationLoaded('manga')) {
                $pageable->load('manga:id,slug');
            }
            $mangaSlug = $pageable->manga?->slug;
        }

        return new BroadcastMessage([
            'type' => 'comment_reply',
            'title' => 'Phản hồi bình luận',
            'message' => "{$this->reply->user->name} đã phản hồi bình luận của bạn",
            'data' => [
                'reply' => [
                    'id' => $this->reply->id,
                    'content' => $this->reply->content,
                    'user' => [
                        'id' => $this->reply->user->id,
                        'name' => $this->reply->user->name,
                        'avatar' => $this->reply->user->avatar,
                    ],
                ],
                'parent_comment' => [
                    'id' => $this->parentComment->id,
                ],
                'manga_slug' => $mangaSlug,
                'comment_id' => $commentId,
            ],
        ]);
    }

    public function toArray(object $notifiable): array
    {
        // Reload relationships in case they weren't serialized with the model
        // Use loadMissing to avoid reloading if already loaded
        if (! $this->reply->relationLoaded('user')) {
            $this->reply->load('user:id,name,avatar');
        }
        if (! $this->reply->relationLoaded('page')) {
            $this->reply->load('page.pageable');
        }

        $page = $this->reply->page;
        $pageable = $page?->pageable;

        $mangaSlug = null;
        $commentId = $this->reply->root_id ?? $this->reply->id;

        if ($pageable instanceof \App\Models\Manga) {
            $mangaSlug = $pageable->slug;
        } elseif ($pageable instanceof \App\Models\MangaChapter) {
            if (! $pageable->relationLoaded('manga')) {
                $pageable->load('manga:id,slug');
            }
            $mangaSlug = $pageable->manga?->slug;
        }

        return [
            'type' => 'comment_reply',
            'title' => 'Phản hồi bình luận',
            'message' => "{$this->reply->user->name} đã phản hồi bình luận của bạn",
            'reply_id' => $this->reply->id,
            'parent_comment_id' => $this->parentComment->id,
            'manga_slug' => $mangaSlug,
            'comment_id' => $commentId,
        ];
    }
}
