<?php

namespace Database\Seeders;

use App\Models\MangaBadge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Str;

class MangaBadegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges =  [
            'Hot',
            'New',
            'Popular',
            'Trending',
            'Featured',
        ];

        foreach ($badges as $badge) {
            MangaBadge::factory()->create([
                'name' => $badge,
                'slug' => Str::slug($badge),
            ]);
        }
    }
}
