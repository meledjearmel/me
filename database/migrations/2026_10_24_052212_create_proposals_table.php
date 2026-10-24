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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 30)->index();
            $table->string('locale', 5);
            $table->string('job_title');
            $table->string('client_name')->nullable();
            $table->string('job_url', 2000)->nullable();
            $table->text('job_posting')->nullable();
            $table->text('body');
            $table->text('screening_answers')->nullable();
            $table->string('bid', 120)->nullable();
            $table->unsignedSmallInteger('connects')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->string('drafted_by', 60)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
