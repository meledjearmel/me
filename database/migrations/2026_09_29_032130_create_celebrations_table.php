<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les « surprises » : un personnage apparaît au hasard sur le site public
     * pour annoncer une bonne nouvelle, et le visiteur peut féliciter d'un clic.
     * La première (la distinction au CIAPOL) est posée d'office.
     */
    public function up(): void
    {
        Schema::create('celebrations', function (Blueprint $table) {
            $table->id();
            $table->json('message');
            $table->json('button_label');
            // Ce qui est célébré, en français et adressé à Armel : complète la notification
            // « Vous avez reçu 3 félicitations pour votre prix de meilleur agent ».
            $table->string('congratulated_for', 150);
            $table->boolean('is_active')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedSmallInteger('weight')->default(1);
            // Fréquence d'apparition : part des visites qui la voient, délai avant
            // qu'Armi ne se manifeste, et durée d'affichage sans interaction.
            $table->unsignedTinyInteger('chance_percent')->default(33);
            $table->unsignedSmallInteger('delay_seconds')->default(9);
            $table->unsignedSmallInteger('display_seconds')->default(15);
            $table->unsignedInteger('congratulations_count')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('celebrations')->insert([
            'message' => json_encode([
                'fr' => 'Le saviez-vous ? Armel a été distingué Meilleur agent du CIAPOL au premier semestre 2025.',
                'en' => 'Did you know? Armel was named Best Agent at CIAPOL for the first half of 2025.',
            ], JSON_UNESCAPED_UNICODE),
            'button_label' => json_encode(['fr' => 'Féliciter', 'en' => 'Congratulate'], JSON_UNESCAPED_UNICODE),
            'congratulated_for' => 'votre distinction de meilleur agent du CIAPOL',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celebrations');
    }
};
