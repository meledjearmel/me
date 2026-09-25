<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_profiles', function (Blueprint $table) {
            $table->json('hero_title')->nullable()->after('description');
            $table->json('hero_words')->nullable()->after('hero_title');
        });

        $heroes = [
            'full-stack' => ['Développeur qui', 'Developer who', 'Code, Conçoit, Livre', 'Codes, Builds, Ships'],
            'charge-it' => ['Chargé IT qui', 'IT Officer who', 'Sécurise, Dépanne, Optimise', 'Secures, Supports, Optimizes'],
            'lead-tech' => ['Tech Lead qui', 'Tech Lead who', 'Guide, Structure, Tranche', 'Guides, Architects, Decides'],
            'chef-projet' => ['Chef projet qui', 'IT Manager who', 'Planifie, Coordonne, Livre', 'Plans, Coordinates, Delivers'],
        ];

        foreach ($heroes as $key => [$titleFr, $titleEn, $wordsFr, $wordsEn]) {
            DB::table('job_profiles')->where('key', $key)->update([
                'hero_title' => json_encode(['fr' => $titleFr, 'en' => $titleEn], JSON_UNESCAPED_UNICODE),
                'hero_words' => json_encode(['fr' => $wordsFr, 'en' => $wordsEn], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_profiles', function (Blueprint $table) {
            $table->dropColumn(['hero_title', 'hero_words']);
        });
    }
};
