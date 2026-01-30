<?php

namespace Database\Seeders;

use App\Models\Manga;
use App\Models\MangaAuthor;
use App\Models\MangaBadge;
use App\Models\MangaCategory;
use App\Models\MangaChapter;
use App\Models\MangaServer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MangaSeeder extends Seeder
{
    // Removed WithoutModelEvents to allow model events (slug generation) to run

    public function run(): void
    {
        // Create Default Server
        $defaultServer = MangaServer::firstOrCreate(
            ['name' => 'Server 1'],
            ['description' => 'Default manga server']
        );

        $categories = MangaCategory::factory()->count(10)->create();

        // Create Authors
        $authors = MangaAuthor::factory()->count(5)->create();

        // Create Badges
        $badges = MangaBadge::factory()->count(5)->create();

        // Get admin user
        $admin = User::where('email', 'admin@vinawebapp.com')->first();

        // Create Mangas
        $mangas = Manga::factory()
            ->count(20)
            ->create([
                'user_id' => $admin?->id ?? User::factory(),
                'avatar'=>'https://picsum.photos/800/1200?random='.fake()->numberBetween(1, 1000),
            ])
            ->each(function (Manga $manga) use ($categories, $authors, $badges, $defaultServer) {
                // Attach random categories
                $manga->categories()->attach(
                    $categories->random(fake()->numberBetween(1, 3))->pluck('id')
                );

                // Set random author if not set
                if (! $manga->manga_author_id) {
                    $manga->update([
                        'manga_author_id' => $authors->random()->id,
                    ]);
                }

                // Set random badge if not set
                if (! $manga->manga_badge_id && fake()->boolean(30)) {
                    $manga->update([
                        'manga_badge_id' => $badges->random()->id,
                    ]);
                }

                // Create chapters for each manga
                $chapterCount = fake()->numberBetween(5, 30);
                for ($i = 0; $i < $chapterCount; $i++) {
                    $chapter = MangaChapter::factory()->create([
                        'manga_id' => $manga->id,
                        'user_id' => $manga->user_id,
                        'order' => $i,
                        'name' => 'Chapter '.($i + 1),
                    ]);

                    // Create chapter content
                    $urls = [];
                    $imageCount = fake()->numberBetween(10, 30);
                    for ($j = 1; $j <= $imageCount; $j++) {
                        $urls[] = 'https://picsum.photos/800/1200?random='.$manga->id.'-'.$i.'-'.$j;
                    }

                    $chapter->serverContents()->create([
                        'manga_server_id' => $defaultServer->id,
                        'urls' => $urls,
                    ]);
                }
            });
    }
}
