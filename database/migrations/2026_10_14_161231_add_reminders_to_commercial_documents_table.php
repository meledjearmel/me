<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les relances : un devis sans réponse ou une facture en retard est signalé chaque jour
     * (reminder_due_at) ; le développeur relance (avec un message relu) ou ignore. Les réglages
     * fixent les délais et le nombre maximal de relances.
     */
    public function up(): void
    {
        Schema::table('commercial_documents', function (Blueprint $table) {
            $table->unsignedTinyInteger('reminders_count')->default(0)->after('paid_at');
            $table->timestamp('last_reminded_at')->nullable()->after('reminders_count');
            // Une relance est suggérée depuis cette date ; null quand rien n'attend.
            $table->timestamp('reminder_due_at')->nullable()->after('last_reminded_at');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('billing_quote_reminder_days')->default(7);
            $table->unsignedSmallInteger('billing_invoice_reminder_days')->default(3);
            $table->unsignedTinyInteger('billing_max_reminders')->default(2);
        });
    }

    public function down(): void
    {
        Schema::table('commercial_documents', function (Blueprint $table) {
            $table->dropColumn(['reminders_count', 'last_reminded_at', 'reminder_due_at']);
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['billing_quote_reminder_days', 'billing_invoice_reminder_days', 'billing_max_reminders']);
        });
    }
};
