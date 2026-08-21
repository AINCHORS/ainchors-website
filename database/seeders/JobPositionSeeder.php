<?php

namespace Database\Seeders;

use App\Models\JobPosition;
use Illuminate\Database\Seeder;

class JobPositionSeeder extends Seeder
{
    public function run(): void
    {
        JobPosition::query()->upsert([
            ['title' => 'Digital Marketer', 'slug' => 'digital-marketer', 'is_active' => true, 'sort_order' => 1],
            ['title' => 'Video Editor', 'slug' => 'video-editor', 'is_active' => true, 'sort_order' => 2],
            ['title' => 'AI Software Engineer', 'slug' => 'ai-software-engineer', 'is_active' => true, 'sort_order' => 3],
        ], ['slug'], ['title', 'is_active', 'sort_order']);
    }
}
