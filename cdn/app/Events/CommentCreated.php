<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Comment $comment
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("page.{$this->comment->page_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'comment' => [
                'id' => $this->comment->id,
                'content' => $this->comment->content,
                'user' => [
                    'id' => $this->comment->user->id,
                    'name' => $this->comment->user->name,
                    'avatar' => $this->comment->user->avatar,
                ],
                'page_id' => $this->comment->page_id,
                'parent_id' => $this->comment->parent_id,
                'root_id' => $this->comment->root_id,
                'depth' => $this->comment->depth ?? 0,
                'created_at' => $this->comment->created_at->toISOString(),
            ],
        ];
    }
}
