<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les demandes de rendez-vous. Le créneau est bloqué dans l'agenda (schedules de Zap)
     * tant que la demande est en attente ou confirmée.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_type_id')->nullable()->constrained()->nullOnDelete();
            // Le créneau réservé dans l'agenda : supprimé quand la demande est refusée ou annulée.
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('location');
            $table->text('message')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('timezone')->nullable();
            $table->string('locale', 5);
            $table->string('status');
            $table->text('meeting_details')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('cancel_token', 64)->unique();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('reminded_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
