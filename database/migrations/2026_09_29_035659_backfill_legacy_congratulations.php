<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REASON = 'Carte « Distinction » de la page À propos';

    /** Plafond de la colonne `count` (unsignedSmallInteger). */
    private const MAX_COUNT_PER_ROW = 65535;

    /**
     * Reprend dans l'historique les félicitations reçues avant qu'il n'existe :
     * elles n'étaient qu'un total (compteur `congratulations`) et venaient toutes
     * de la carte « Distinction » (meilleur agent du CIAPOL) de la page À propos.
     * La langue des visiteurs est inconnue (`locale` à null). Aucune notification.
     * Le compteur lui-même n'est pas modifié : le total affiché ne change pas.
     */
    public function up(): void
    {
        $counter = DB::table('counters')->where('key', 'congratulations')->first();

        if ($counter === null || $counter->total <= 0) {
            return;
        }

        $remaining = (int) $counter->total;

        while ($remaining > 0) {
            $count = min($remaining, self::MAX_COUNT_PER_ROW);

            DB::table('congratulations')->insert([
                'source' => 'about',
                'celebration_id' => null,
                'reason' => self::REASON,
                'count' => $count,
                'locale' => null,
                'created_at' => $counter->created_at,
                'updated_at' => $counter->updated_at,
            ]);

            $remaining -= $count;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('congratulations')
            ->where('source', 'about')
            ->where('reason', self::REASON)
            ->whereNull('locale')
            ->delete();
    }
};
