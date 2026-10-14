<?php

namespace App\Models;

use App\Concerns\PlacesNewAtEnd;
use Database\Factories\EstimatorRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Une grille tarifaire de l'estimateur : des taux horaires nommés, par devise, partagés par
 * les types de projet et options qui y sont rattachés. Une devise sans taux dans la grille
 * suit le taux global des réglages.
 */
class EstimatorRate extends Model
{
    /** @use HasFactory<EstimatorRateFactory> */
    use HasFactory, HasTranslations, PlacesNewAtEnd;

    /** @var array<int, string> */
    protected $translatable = ['name'];

    /** @var list<string> */
    protected $fillable = ['name', 'hourly_rates', 'sort_order'];

    /** @var array<string, string> */
    protected $casts = [
        'hourly_rates' => 'array',
        'sort_order' => 'integer',
    ];

    /** @return HasMany<EstimatorItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(EstimatorItem::class);
    }

    /**
     * Taux renseignés, limités aux devises connues.
     *
     * @return array<string, int>
     */
    public function rates(): array
    {
        return EstimatorItem::filterRates($this->hourly_rates);
    }
}
