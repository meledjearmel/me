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
            'status' => TestimonialStatus::Approved,
        ])->each(fn (Testimonial $testimonial) => $testimonial->projects()->attach($projects->random(min(2, $projects->count()))));

        Testimonial::factory()->count(2)->create();
    }
}
