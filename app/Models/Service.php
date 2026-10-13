<?php

namespace App\Models;

use App\Concerns\HasPublicationStatus;
use App\Concerns\PlacesNewAtEnd;
use App\Enums\ServicePriceMode;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * Une offre de la page « Services » : ce que je propose, ce qui est compris (une ligne par
 * élément), un prix « à partir de » et les projets qui l'illustrent. Son type de projet
 * de l'estimateur sert au prix calculé et au lien « Estimer mon projet ».
 *
 * @property array<string, int>|null $manual_prices
 */
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasPublicationStatus, HasTranslations, PlacesNewAtEnd, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['title', 'tagline', 'description', 'includes'];

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'title',
        'tagline',
        'description',
        'includes',
        'price_mode',
        'estimator_item_id',
        'manual_prices',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'price_mode' => ServicePriceMode::class,
        'manual_prices' => 'array',
        'sort_order' => 'integer',
    ];

    /** @return BelongsTo<EstimatorItem, $this> */
    public function estimatorItem(): BelongsTo
    {
        return $this->belongsTo(EstimatorItem::class);
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_service');
    }

    /**
     * Ce qui est compris, dans une langue : une ligne par élément, lignes vides ignorées.
     *
     * @return list<string>
     */
    public function includesList(string $locale): array
    {
        return collect(preg_split('/\R/', (string) $this->getTranslation('includes', $locale)))
            ->map(fn (string $line): string => trim($line, " \t-•"))
            ->filter()
            ->values()
            ->all();
    }
}
