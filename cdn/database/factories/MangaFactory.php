<?php

namespace Database\Factories;

use App\Models\Manga;
use App\Models\MangaAuthor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Manga>
 */
class MangaFactory extends Factory
{
    protected $model = Manga::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'manga_author_id' => MangaAuthor::factory(),
            'manga_badge_id' => null,
            'user_id' => User::factory(),
            'name' => ucwords($name),
            'slug' => null, // Let model auto-generate unique slug
            'description' => fake()->optional()->paragraphs(3, true),
            'status' => fake()->randomElement(['ongoing', 'completed', 'hiatus', 'cancelled']),
            'total_views' => fake()->numberBetween(0, 1000000),
            'monthly_views' => fake()->numberBetween(0, 100000),
            'weekly_views' => fake()->numberBetween(0, 50000),
            'daily_views' => fake()->numberBetween(0, 10000),
            'total_follow' => fake()->numberBetween(0, 50000),
            'rating' => fake()->randomFloat(1, 0, 5),
            'total_ratings' => fake()->numberBetween(0, 10000),
        ];
    }

    public function withCategories(): static
    {
        return $this->afterCreating(function (Manga $manga) {
            $categories = \App\Models\MangaCategory::factory()->count(fake()->numberBetween(1, 3))->create();
            $manga->categories()->attach($categories->pluck('id'));
        });
    }
}
