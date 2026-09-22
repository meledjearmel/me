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
            if ($domains->isNotEmpty()) {
                $project->domains()->attach($domains->random(min(2, $domains->count())));
            }

            if ($jobProfiles->isNotEmpty()) {
                $project->jobProfiles()->attach($jobProfiles->random(min(2, $jobProfiles->count())));
            }

            if ($technologies->isNotEmpty()) {
                $project->technologies()->attach($technologies->random(min(5, $technologies->count())));
            }
        });
    }
}
