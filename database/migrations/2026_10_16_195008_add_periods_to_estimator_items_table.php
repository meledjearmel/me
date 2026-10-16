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
        Schema::table('estimator_items', function (Blueprint $table) {
            // Options récurrentes : périodes proposées et leur remise en %, ex. {"monthly": 0, "yearly": 15}.
            $table->json('periods')->nullable()->after('percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimator_items', function (Blueprint $table) {
            $table->dropColumn('periods');
        });
    }
};
