<?php

namespace App\Models;

use Database\Factories\BookingSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Zap\Models\Concerns\HasSchedules;

/**
 * Réglages de la prise de rendez-vous, sur une seule ligne. Porte aussi mon agenda
 * (disponibilités, périodes bloquées et créneaux des rendez-vous, via Zap).
 */
class BookingSetting extends Model
{
    /** @use HasFactory<BookingSettingFactory> */
    use HasFactory, HasSchedules;

    /** @var list<string> */
    protected $fillable = [
        'is_enabled',
        'min_notice_hours',
        'horizon_days',
        'buffer_minutes',
        'video_link',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_enabled' => 'boolean',
        'min_notice_hours' => 'integer',
        'horizon_days' => 'integer',
        'buffer_minutes' => 'integer',
    ];

    /** Les réglages, créés avec leurs valeurs par défaut au premier accès. */
    public static function current(): self
    {
        // refresh() : une ligne tout juste créée récupère les valeurs par défaut de la base.
        return static::query()->first() ?? static::query()->create()->refresh();
    }
}
