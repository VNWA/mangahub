<?php

namespace Database\Factories;

use App\Models\MangaBadge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MangaBadge>
 */
class MangaBadgeFactory extends Factory
{
    protected $model = MangaBadge::class;

    public function definition(): array
    {
        $badges = ['Hot', 'New', 'Popular', 'Trending', 'Completed', 'Ongoing', 'Top Rated'];
        $name = fake()->randomElement($badges);
        $colors = [
            ['#FF0000', '#FFE5E5', '#FFFFFF', '#000000'],
            ['#00FF00', '#E5FFE5', '#000000', '#FFFFFF'],
            ['#0000FF', '#E5E5FF', '#FFFFFF', '#000000'],
            ['#FFA500', '#FFF5E5', '#000000', '#FFFFFF'],
            ['#800080', '#F0E5F0', '#FFFFFF', '#000000'],
        ];
        $colorSet = fake()->randomElement($colors);

        return [
            'name' => $name,
            'slug' => null, // Let model auto-generate unique slug
            'light_text_color' => $colorSet[2],
            'light_bg_color' => $colorSet[1],
            'dark_text_color' => $colorSet[3],
            'dark_bg_color' => $colorSet[0],
        ];
    }
}
