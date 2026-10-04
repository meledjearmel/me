<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Une série supprimée laisse ses articles en place, hors série.
            $table->foreignId('post_series_id')->nullable()->after('slug')->constrained('post_series')->nullOnDelete();
            $table->unsignedSmallInteger('series_position')->nullable()->after('post_series_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_series_id');
            $table->dropColumn('series_position');
        });
    }
};
