<?php

namespace App\Models;

use Database\Factories\EstimateSimulationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Une simulation de l'estimateur par un visiteur anonyme : la configuration essayée (type de
 * projet, options) et la dernière fourchette vue. Une seule par visiteur, jour et configuration
 * (l'empreinte du visiteur change chaque jour). Gardée un an.
 */
class EstimateSimulation extends Model
{
    /** @use HasFactory<EstimateSimulationFactory> */
    use HasFactory, MassPrunable;

    public const int RETENTION_DAYS = 365;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'visitor_hash',
        'signature',
        'project_type_id',
        'project_type',
        'features',
        'currency',
        'price_min',
        'price_max',
    ];

    /** @var list<string> */
    protected $hidden = ['visitor_hash', 'signature'];

    /** @var array<string, string> */
    protected $casts = [
        'features' => 'array',
        'price_min' => 'integer',
        'price_max' => 'integer',
    ];

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }
}
