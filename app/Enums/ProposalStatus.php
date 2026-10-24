<?php

namespace App\Enums;

/** Où en est une proposition envoyée sur une plateforme freelance, du brouillon au résultat. */
enum ProposalStatus: string
{
    /** Rédigée, pas encore envoyée. */
    case Draft = 'draft';
    /** Envoyée, sans réaction du client. */
    case Sent = 'sent';
    /** Le client l'a ouverte. */
    case Viewed = 'viewed';
    /** Le client a répondu ou proposé un échange. */
    case Interview = 'interview';
    /** Mission obtenue. */
    case Hired = 'hired';
    /** Refusée, ou offre fermée sans suite. */
    case Declined = 'declined';
}
