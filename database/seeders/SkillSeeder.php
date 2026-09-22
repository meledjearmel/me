<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Domain::all()->each(function (Domain $domain): void {
            Skill::factory()->count(4)->create(['domain_id' => $domain->id]);
        });
    }
}
