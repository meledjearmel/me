<?php

namespace Database\Seeders;

use App\Enums\TestimonialStatus;
use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();

        Testimonial::factory()->count(6)->create([
            'project_id' => fn () => $projects->random()->id,
            'status' => TestimonialStatus::Approved,
        ]);

        Testimonial::factory()->count(2)->create();
    }
}
