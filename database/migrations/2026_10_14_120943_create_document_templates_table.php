<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le design de chaque type de document (devis, proforma, facture) : mise en page, couleurs,
     * police, logo (médiathèque), textes propres et blocs affichés.
     */
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            // quote, proforma ou invoice : un modèle par type.
            $table->string('type')->unique();
            // classic, modern ou minimal.
            $table->string('layout')->default('classic');
            $table->string('primary_color', 7)->default('#0f2747');
            $table->string('accent_color', 7)->default('#ffda3f');
            // sans, serif ou mono (polices DejaVu, intégrées à dompdf).
            $table->string('font')->default('sans');
            // Titre à la place de « Devis », « Facture »… ; textes avant les lignes et en pied de page.
            $table->json('title')->nullable();
            $table->json('intro')->nullable();
            $table->json('footer')->nullable();
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_bank_details')->default(true);
            // Bloc « Bon pour accord » avec date et signature du client.
            $table->boolean('show_signature')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
