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
        Schema::create('signing_key_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->char('sha256', 64);
            $table->string('key_fingerprint', 95);
            $table->string('encryption', 50);
            $table->text('notes')->nullable();
            $table->boolean('has_file')->default(false);
            $table->timestamp('replaced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signing_key_backups');
    }
};
