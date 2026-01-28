<?php

namespace Database\Factories;

use App\Models\MangaAuthor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MangaAuthor>
 */
class MangaAuthorFactory extends Factory
{
    protected $model = MangaAuthor::class;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'slug' => null, // Let model auto-generate unique slug
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
