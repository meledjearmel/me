<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nombre de jours pendant lesquels une surprise fermée par le visiteur ne lui
     * est plus proposée (0 = elle peut revenir dès la session suivante).
     */
    public function up(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->unsignedSmallInteger('snooze_days')->default(7)->after('display_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->dropColumn('snooze_days');
        });
    }
};
