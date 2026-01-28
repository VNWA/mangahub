<?php

namespace App\Notifications;

use App\Models\MangaChapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewChapterNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public function __construct(
        public MangaChapter $chapter
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'new_chapter',
            'message' => "{$this->chapter->manga->name} có chapter mới: {$this->chapter->name}",
            'data' => [
                'chapter' => [
                    'id' => $this->chapter->id,
                    'name' => $this->chapter->name,
                    'slug' => $this->chapter->slug,
                    'manga' => [
                        'id' => $this->chapter->manga->id,
                        'name' => $this->chapter->manga->name,
                        'slug' => $this->chapter->manga->slug,
                    ],
                ],
            ],
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_chapter',
            'message' => "{$this->chapter->manga->name} có chapter mới: {$this->chapter->name}",
            'chapter_id' => $this->chapter->id,
            'manga_id' => $this->chapter->manga->id,
        ];
    }
}
