<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L'issue d'une demande : signée (montant et devise réellement signés) ou perdue, et sa
     * date, pour comparer ce qui a été estimé à ce qui a été gagné.
     */
    public function up(): void
    {
        Schema::table('engagements', function (Blueprint $table) {
            // won ou lost ; null tant que la demande n'a pas d'issue.
            $table->string('outcome')->nullable()->after('status');
            $table->unsignedInteger('outcome_amount')->nullable()->after('outcome');
            $table->string('outcome_currency', 3)->nullable()->after('outcome_amount');
            $table->timestamp('outcome_at')->nullable()->after('outcome_currency');
        });
    }

    public function down(): void
    {
        Schema::table('engagements', function (Blueprint $table) {
            $table->dropColumn(['outcome', 'outcome_amount', 'outcome_currency', 'outcome_at']);
        });
    }
};
