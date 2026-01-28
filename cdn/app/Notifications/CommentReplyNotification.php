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
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'comment_reply',
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
            ],
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'comment_reply',
            'message' => "{$this->reply->user->name} đã phản hồi bình luận của bạn",
            'reply_id' => $this->reply->id,
            'parent_comment_id' => $this->parentComment->id,
        ];
    }
}
