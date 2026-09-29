<?php

namespace App\Models;

use Database\Factories\CelebrationFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Une « surprise » du site public : un personnage apparaît au hasard pour
 * annoncer une bonne nouvelle, et le visiteur peut féliciter d'un clic.
 */
class Celebration extends Model
{
    /** @use HasFactory<CelebrationFactory> */
    use HasFactory, HasTranslations;

    /** @var array<int, string> */
    protected $translatable = ['message', 'button_label'];

    /** @var list<string> */
    protected $fillable = [
        'message',
        'button_label',
        'congratulated_for',
        'is_active',
        'starts_at',
        'ends_at',
        'weight',
        'chance_percent',
        'delay_seconds',
        'display_seconds',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'date:Y-m-d',
        'ends_at' => 'date:Y-m-d',
        'weight' => 'integer',
        'chance_percent' => 'integer',
        'delay_seconds' => 'integer',
        'display_seconds' => 'integer',
        'congratulations_count' => 'integer',
    ];

    /** Actives et dans leur période d'affichage (bornes incluses). */
    #[Scope]
    protected function showable(Builder $query): void
    {
        $today = today();

        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today));
    }

    /** Tirage au sort pondéré parmi les surprises affichables (null s'il n'y en a aucune). */
    public static function pickRandom(): ?self
    {
        $celebrations = static::query()->showable()->get();
        $totalWeight = (int) $celebrations->sum('weight');

        if ($totalWeight <= 0) {
            return null;
        }

        $ticket = random_int(1, $totalWeight);

        return $celebrations->first(function (self $celebration) use (&$ticket): bool {
            $ticket -= $celebration->weight;

            return $ticket <= 0;
        });
    }
}
