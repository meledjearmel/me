<?php

namespace App\Models;

use App\Concerns\PlacesNewAtEnd;
use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use Database\Factories\EstimatorItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * Un élément de l'estimateur de projet : un type de projet (le socle, avec son mode de
 * facturation) ou une option qui s'y ajoute, chiffré en heures de travail.
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
        'billing',
        'is_active',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'kind' => EstimatorItemKind::class,
        'billing' => EstimatorBilling::class,
        'hours_min' => 'integer',
        'hours_max' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /** Une option n'a pas de mode de facturation : elle suit celui du type de projet. */
    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if ($item->kind === EstimatorItemKind::Feature) {
                $item->billing = null;
            } elseif ($item->billing === null) {
                $item->billing = EstimatorBilling::Fixed;
            }
        });
    }
}
