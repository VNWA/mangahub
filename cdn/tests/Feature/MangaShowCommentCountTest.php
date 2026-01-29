<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Manga;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MangaShowCommentCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_manga_show_includes_comment_count(): void
    {
        $manga = Manga::factory()->create();
        $page = Page::getOrCreateFor($manga);

        $user = User::factory()->create();

        $root = Comment::create([
            'user_id' => $user->id,
            'page_id' => $page->id,
            'parent_id' => null,
            'root_id' => null,
            'content' => 'Root comment',
        ]);

        $reply = Comment::create([
            'user_id' => $user->id,
            'page_id' => $page->id,
            'parent_id' => $root->id,
            'root_id' => $root->id,
            'depth' => 1,
            'content' => 'Reply',
        ]);

        Comment::create([
            'user_id' => $user->id,
            'page_id' => $page->id,
            'parent_id' => $reply->id,
            'root_id' => $root->id,
            'depth' => 2,
            'content' => 'Reply of reply',
        ]);

        $page->updateCommentsCount();

        $response = $this->getJson("/api/v1/mangas/{$manga->slug}");

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.comment_count', 3);
    }
}
