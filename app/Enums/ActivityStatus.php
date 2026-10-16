<?php

namespace App\Enums;

/**
 * Statut d'une ligne qui n'a qu'un interrupteur `is_active` (élément de l'estimateur, type de
 * rendez-vous, célébration) : proposée sur le site ou masquée.
 */
enum ActivityStatus: string
{
    case Active = 'active';
    case Hidden = 'hidden';
}
