<?php

namespace App\Models;

use App\Enums\CommercialDocumentType;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * Le design d'un type de document (devis, proforma ou facture) : mise en page, couleurs, police,
 * logo, textes propres et blocs affichés. Créé avec les valeurs par défaut au premier besoin.
 */
class DocumentTemplate extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    public const array LAYOUTS = ['classic', 'modern', 'minimal'];

    /** Polices proposées, intégrées à dompdf (aucun accès réseau). */
    public const array FONTS = ['sans' => 'DejaVu Sans', 'serif' => 'DejaVu Serif', 'mono' => 'DejaVu Sans Mono'];

    /** @var array<int, string> */
    protected $translatable = ['title', 'intro', 'footer'];

    /** @var list<string> */
    protected $fillable = [
        'type',
        'layout',
        'primary_color',
        'accent_color',
        'font',
        'title',
        'intro',
        'footer',
        'show_logo',
        'show_bank_details',
        'show_signature',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type' => CommercialDocumentType::class,
        'show_logo' => 'boolean',
        'show_bank_details' => 'boolean',
        'show_signature' => 'boolean',
    ];

    /** Le modèle d'un type, créé avec les valeurs par défaut s'il n'existe pas encore. */
    public static function for(CommercialDocumentType $type): self
    {
        $template = self::query()->firstOrCreate(['type' => $type], ['show_signature' => $type === CommercialDocumentType::Quote]);

        if ($template->wasRecentlyCreated) {
            // Relu pour avoir les valeurs par défaut de la base (mise en page, couleurs, police…).
            $template->refresh();
            // Une création implicite : les réponses de l'API ne doivent pas devenir des « 201 Created ».
            $template->wasRecentlyCreated = false;
        }

        return $template;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    /** Le logo en data URI (dompdf lit les images sans accès réseau) ; null sans logo. */
    public function logoDataUri(): ?string
    {
        $media = $this->getFirstMedia('logo');

        if ($media === null || ! is_file($media->getPath())) {
            return null;
        }

        return 'data:'.$media->mime_type.';base64,'.base64_encode((string) file_get_contents($media->getPath()));
    }

    public function fontFamily(): string
    {
        return self::FONTS[$this->font] ?? self::FONTS['sans'];
    }
}
