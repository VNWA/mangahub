<?php

namespace App\Listeners;

use App\Events\CommentReplied;
use App\Notifications\CommentReplyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCommentReplyNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CommentReplied $event): void
    {
        // Don't notify if user replies to their own comment
        if ($event->parentComment->user_id === $event->reply->user_id) {
            return;
        }

        $event->parentComment->user->notify(
            new CommentReplyNotification($event->reply, $event->parentComment)
        );
    }
}
