<?php

namespace App\Models;

use App\Concerns\PlacesNewAtEnd;
use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Enums\EstimatorPricing;
use Database\Factories\EstimatorItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * Un élément de l'estimateur de projet : un type de projet (le socle, chiffré en heures,
 * avec son mode de facturation) ou une option qui s'y ajoute, en heures ou en pourcentage
 * des heures du projet. Une option peut être réservée à certains types de projet.
 * Son taux horaire : ses propres taux s'il en a, sinon sa grille tarifaire, sinon le taux global.
 */
class EstimatorItem extends Model
{
    /** @use HasFactory<EstimatorItemFactory> */
    use HasFactory, HasTranslations, PlacesNewAtEnd, SoftDeletes;

    /** Devises que l'estimateur sait proposer, si leur taux horaire est renseigné. */
    public const CURRENCIES = ['CHF', 'EUR', 'USD', 'XOF'];

    /** @var array<int, string> */
    protected $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'kind',
        'name',
        'description',
        'hours_min',
        'hours_max',
        'pricing',
        'percent',
        'billing',
        'estimator_rate_id',
        'hourly_rates',
        'is_active',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'kind' => EstimatorItemKind::class,
        'billing' => EstimatorBilling::class,
        'pricing' => EstimatorPricing::class,
        'hours_min' => 'integer',
        'hours_max' => 'integer',
        'percent' => 'integer',
        'hourly_rates' => 'array',
        'is_active' => 'boolean',
    ];

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Option : les types de projet auxquels elle est réservée (aucun : proposée pour tous).
     *
     * @return BelongsToMany<EstimatorItem, $this>
     */
    public function projectTypes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'estimator_feature_project_type', 'feature_id', 'project_type_id');
    }

    /**
     * La grille tarifaire de l'élément (aucune : ses propres taux ou le taux global).
     *
     * @return BelongsTo<EstimatorRate, $this>
     */
    public function rate(): BelongsTo
    {
        return $this->belongsTo(EstimatorRate::class, 'estimator_rate_id');
    }

    /**
     * Taux horaire de l'élément dans cette devise : le sien, sinon celui de sa grille,
     * sinon le taux global (0 si aucun).
     *
     * @param  array<string, int>  $globalRates
     */
    public function hourlyRate(string $currency, array $globalRates): int
    {
        return self::filterRates($this->hourly_rates)[$currency]
            ?? $this->rate?->rates()[$currency]
            ?? $globalRates[$currency]
            ?? 0;
    }

    /**
     * Garde les taux des devises connues qui sont renseignés (plus de 0).
     *
     * @param  array<string, mixed>|null  $rates
     * @return array<string, int>
     */
    public static function filterRates(?array $rates): array
    {
        $filtered = [];

        foreach (self::CURRENCIES as $currency) {
            $rate = (int) ($rates[$currency] ?? 0);

            if ($rate > 0) {
                $filtered[$currency] = $rate;
            }
        }

        return $filtered;
    }

    /** Une option est-elle proposée pour ce type de projet ? */
    public function isAvailableFor(int $projectTypeId): bool
    {
        $ids = $this->relationLoaded('projectTypes')
            ? $this->projectTypes->modelKeys()
            : $this->projectTypes()->pluck('estimator_items.id')->all();

        return $ids === [] || in_array($projectTypeId, $ids, true);
    }

    /**
     * Garde des données cohérentes : seule une option a un mode de prix (un type de projet
     * se chiffre en heures) et seul un type de projet a un mode de facturation ; une option
     * en pourcentage n'a pas d'heures.
     */
    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if ($item->kind === EstimatorItemKind::Feature) {
                $item->billing = null;
                $item->pricing ??= EstimatorPricing::Hours;
            } else {
                $item->billing ??= EstimatorBilling::Fixed;
                $item->pricing = EstimatorPricing::Hours;
            }

            if ($item->pricing === EstimatorPricing::Percent) {
                $item->hours_min = 0;
                $item->hours_max = 0;
                $item->percent ??= 0;
            } else {
                $item->percent = null;
            }

            $item->hourly_rates = self::filterRates($item->hourly_rates) ?: null;
        });
    }
}
