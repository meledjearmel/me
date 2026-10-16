<?php

namespace App\Enums;

/** Période de facturation d'une option récurrente (maintenance, hébergement…). */
enum EstimatorPeriod: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Semiannual = 'semiannual';
    case Yearly = 'yearly';

    /** Nombre de mois couverts par la période. */
    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::Semiannual => 6,
            self::Yearly => 12,
        };
    }

    /** Unité de la ligne de devis correspondante. */
    public function unit(): string
    {
        return match ($this) {
            self::Monthly => 'month',
            self::Quarterly => 'quarter',
            self::Semiannual => 'semester',
            self::Yearly => 'year',
        };
    }

    /** Libellé « par … » en français. */
    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'mois',
            self::Quarterly => 'trimestre',
            self::Semiannual => 'semestre',
            self::Yearly => 'an',
        };
    }
}
