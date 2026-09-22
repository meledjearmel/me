<?php

namespace Database\Seeders;

use App\Models\Experience;
use App\Models\ExperienceHighlight;
use Illuminate\Database\Seeder;

class ExperienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Experience::factory()->count(4)->create()->each(function (Experience $experience): void {
            ExperienceHighlight::factory()->count(3)->create(['experience_id' => $experience->id]);
        });
    }
}
