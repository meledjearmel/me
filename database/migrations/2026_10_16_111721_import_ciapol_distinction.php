<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La carte « Distinction » de la page À propos était écrite en dur dans les traductions :
     * elle devient la première distinction, rattachée à l'expérience au CIAPOL et avec les
     * félicitations déjà reçues.
     */
    public function up(): void
    {
        $now = now();
        $experienceId = DB::table('experiences')->where('company', 'like', '%CIAPOL%')->value('id');
        $congratulations = (int) DB::table('counters')->where('key', 'congratulations')->value('total');

        // Installation neuve (tests compris) : rien à reprendre.
        if ($experienceId === null && $congratulations === 0) {
            return;
        }

        $id = DB::table('distinctions')->insertGetId([
            'title' => json_encode([
                'fr' => 'Meilleur agent du premier semestre 2025',
                'en' => 'Best agent of the first half of 2025',
            ], JSON_UNESCAPED_UNICODE),
            'issuer' => 'CIAPOL',
            'awarded_on' => '2025-06-30',
            'distinguishable_type' => $experienceId !== null ? 'App\\Models\\Experience' : null,
            'distinguishable_id' => $experienceId,
            'congratulations_count' => $congratulations,
            'status' => 'published',
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('congratulations')->where('source', 'about')->update(['distinction_id' => $id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('distinctions')->where('issuer', 'CIAPOL')->delete();
    }
};
