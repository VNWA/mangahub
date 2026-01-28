<?php

namespace Database\Factories;

use App\Models\MangaServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MangaServer>
 */
class MangaServerFactory extends Factory
{
    protected $model = MangaServer::class;

    public function definition(): array
    {
        $servers = ['Default', 'Server 1', 'Server 2', 'CDN Server', 'Backup Server'];

        return [
            'name' => fake()->unique()->randomElement($servers),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
