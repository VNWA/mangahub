<?php

namespace App\Events;

use App\Models\MangaChapter;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChapterCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MangaChapter $chapter
    ) {
    }

    public function broadcastOn(): array
    {
        $manga = $this->chapter->manga;
        $favoriteUserIds = \App\Models\Favorite::where('manga_id', $manga->id)
            ->pluck('user_id')
            ->toArray();

        $channels = array_map(fn ($userId) => new Channel("user.{$userId}"), $favoriteUserIds);

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'chapter.created';
    }

    public function broadcastWith(): array
    {
        return [
            'chapter' => [
                'id' => $this->chapter->id,
                'name' => $this->chapter->name,
                'slug' => $this->chapter->slug,
                'order' => $this->chapter->order,
                'manga' => [
                    'id' => $this->chapter->manga->id,
                    'name' => $this->chapter->manga->name,
                    'slug' => $this->chapter->manga->slug,
                ],
                'created_at' => $this->chapter->created_at->toISOString(),
            ],
        ];
    }
}
