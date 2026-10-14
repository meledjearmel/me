<?php

namespace App\Enums;

/** Les documents commerciaux, chacun avec son préfixe de numéro. */
enum CommercialDocumentType: string
{
    case Quote = 'quote';
    case Proforma = 'proforma';
    case Invoice = 'invoice';

    public function prefix(): string
    {
        return match ($this) {
            self::Quote => 'ADEV-DEV',
            self::Proforma => 'ADEV-PRO',
            self::Invoice => 'ADEV-FAC',
        };
    }

    public function label(string $locale = 'fr'): string
    {
        return match ($this) {
            self::Quote => $locale === 'en' ? 'Quote' : 'Devis',
            self::Proforma => $locale === 'en' ? 'Proforma invoice' : 'Facture proforma',
            self::Invoice => $locale === 'en' ? 'Invoice' : 'Facture',
        };
    }
}
