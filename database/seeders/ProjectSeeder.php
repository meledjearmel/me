<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\JobProfile;
use App\Models\Project;
use App\Models\Technology;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domains = Domain::all();
        $jobProfiles = JobProfile::all();
        $technologies = Technology::all();

        Project::factory()->count(10)->create()->each(function (Project $project) use ($domains, $jobProfiles, $technologies): void {
            $project->domains()->attach($domains->random(random_int(1, min(2, $domains->count()))));
            $project->jobProfiles()->attach($jobProfiles->random(random_int(1, min(2, $jobProfiles->count()))));
            $project->technologies()->attach($technologies->random(random_int(2, min(5, $technologies->count()))));
        });
    }
}
