<?php

namespace Database\Seeders;

use App\Models\ProfessionalReference;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProfessionalReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();

        ProfessionalReference::factory()->count(4)->create([
            'project_id' => fn () => $projects->random()->id,
        ]);
    }
}
