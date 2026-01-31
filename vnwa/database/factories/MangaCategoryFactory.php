<?php

namespace Database\Factories;

use App\Models\MangaCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MangaCategory>
 */
class MangaCategoryFactory extends Factory
{
    protected $model = MangaCategory::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => null, // Let model auto-generate unique slug
            'description' => fake()->optional()->sentence(),
            'icon' => fake()->optional()->randomElement(['📚', '🎭', '⚔️', '❤️', '🔮', '🌟', '🎨', '🏰']),
        ];
    }
}
