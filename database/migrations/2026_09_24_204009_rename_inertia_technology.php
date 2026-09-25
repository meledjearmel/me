<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('technologies')->where('name', 'Inertia.js')->update(['name' => 'Inertia']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('technologies')->where('name', 'Inertia')->update(['name' => 'Inertia.js']);
    }
};
