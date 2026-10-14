<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DocumentTemplate
 */
class DocumentTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** `quote`, `proforma` ou `invoice`. */
            'type' => $this->type,
            /** `classic`, `modern` (bandeau de couleur) ou `minimal` (sans aplat). */
            'layout' => $this->layout,
            /** Couleur principale, hexadécimale (`#0f2747`). */
            'primary_color' => $this->primary_color,
            /** Couleur d'accent, hexadécimale. */
            'accent_color' => $this->accent_color,
            /** `sans`, `serif` ou `mono`. */
            'font' => $this->font,
            /**
             * Titre à la place du nom du type ; absent : « Devis », « Facture »…
             *
             * @var array{fr?: string, en?: string}
             */
            'title' => $this->getTranslations('title'),
            /**
             * Texte affiché avant les lignes.
             *
             * @var array{fr?: string, en?: string}
             */
            'intro' => $this->getTranslations('intro'),
            /**
             * Texte ajouté au pied de page.
             *
             * @var array{fr?: string, en?: string}
             */
            'footer' => $this->getTranslations('footer'),
            'show_logo' => $this->show_logo,
            'show_bank_details' => $this->show_bank_details,
            /** Bloc « Bon pour accord » avec date et signature du client. */
            'show_signature' => $this->show_signature,
            'logo_url' => $this->getFirstMediaUrl('logo') ?: null,
        ];
    }
}
