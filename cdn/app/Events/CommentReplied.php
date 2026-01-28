<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentReplied implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Comment $reply,
        public Comment $parentComment
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("user.{$this->parentComment->user_id}"),
            new Channel("page.{$this->reply->page_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.replied';
    }

    public function broadcastWith(): array
    {
        return [
            'reply' => [
                'id' => $this->reply->id,
                'content' => $this->reply->content,
                'user' => [
                    'id' => $this->reply->user->id,
                    'name' => $this->reply->user->name,
                    'avatar' => $this->reply->user->avatar,
                ],
                'parent_id' => $this->reply->parent_id,
                'root_id' => $this->reply->root_id,
                'depth' => $this->reply->depth ?? 0,
                'created_at' => $this->reply->created_at->toISOString(),
            ],
            'parent_comment' => [
                'id' => $this->parentComment->id,
            ],
        ];
    }
}
