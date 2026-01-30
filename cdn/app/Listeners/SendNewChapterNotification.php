<?php

namespace App\Listeners;

use App\Events\ChapterCreated;
use App\Models\Favorite;
use App\Notifications\NewChapterNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNewChapterNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ChapterCreated $event): void
    {
        // Ensure chapter has manga relationship loaded
        $event->chapter->loadMissing('manga');
        $manga = $event->chapter->manga;

        if (! $manga) {
            return;
        }

        // Get all users who favorited this manga
        $favoriteUserIds = Favorite::where('manga_id', $manga->id)
            ->pluck('user_id')
            ->unique();

        foreach ($favoriteUserIds as $userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $user->notify(new NewChapterNotification($event->chapter));
            }
        }
    }
}
