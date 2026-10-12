<?php

namespace App\Models;

use Database\Factories\McpActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Un appel au serveur MCP (outil ou commande) : quel client, quoi, sur quel contenu, avec
 * quel résultat. Supprimé après 90 jours (model:prune).
 */
class McpActivity extends Model
{
    /** @use HasFactory<McpActivityFactory> */
    use HasFactory, MassPrunable;

    public const int RETENTION_DAYS = 90;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'client',
        'kind',
        'name',
        'target_type',
        'target_id',
        'success',
        'error',
        'duration_ms',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'success' => 'boolean',
        'duration_ms' => 'integer',
    ];

    /** @return Builder<static> */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }
}
