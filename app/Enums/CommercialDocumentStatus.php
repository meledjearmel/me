<?php

namespace App\Enums;

/**
 * Le cycle d'un document : brouillon modifiable, émis (numéroté, figé), puis accepté ou refusé
 * (devis), payé (facture) ou annulé.
 */
enum CommercialDocumentStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
