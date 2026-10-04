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
        Schema::table('projects', function (Blueprint $table) {
            $table->json('challenges')->nullable()->after('context');
            $table->json('decisions')->nullable()->after('realization');
            $table->date('started_on')->nullable()->after('platform');
            $table->date('ended_on')->nullable()->after('started_on');
            $table->unsignedTinyInteger('team_size')->nullable()->after('ended_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['challenges', 'decisions', 'started_on', 'ended_on', 'team_size']);
        });
    }
};
