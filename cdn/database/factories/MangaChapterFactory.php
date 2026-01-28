<?php

namespace Database\Factories;

use App\Models\Manga;
use App\Models\MangaChapter;
use App\Models\MangaServer;
use App\Models\ServerChapterContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MangaChapter>
 */
class MangaChapterFactory extends Factory
{
    protected $model = MangaChapter::class;

    public function definition(): array
    {
        $name = 'Chapter '.fake()->numberBetween(1, 500);

        return [
            'manga_id' => Manga::factory(),
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => null, // Let model auto-generate unique slug
            'description' => fake()->optional()->sentence(),
            'order' => 0,
        ];
    }

    public function withContent(): static
    {
        return $this->afterCreating(function (MangaChapter $chapter) {
            $server = MangaServer::firstOrCreate(
                ['name' => 'Default'],
                ['description' => 'Default manga server']
            );

            $urls = [];
            $imageCount = fake()->numberBetween(10, 30);
            for ($i = 1; $i <= $imageCount; $i++) {
                $urls[] = 'https://picsum.photos/800/1200?random='.$i;
            }

            ServerChapterContent::create([
                'manga_server_id' => $server->id,
                'manga_chapter_id' => $chapter->id,
                'urls' => $urls,
            ]);
        });
    }
}
