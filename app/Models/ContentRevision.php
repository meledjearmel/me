<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use Database\Factories\ContentRevisionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modification d'un contenu publié (ou de la page « Now ») proposée par l'IA via le serveur
 * MCP : rien ne change sur le site tant qu'elle n'est pas appliquée depuis l'administration.
 */
class ContentRevision extends Model
{
    /** @use HasFactory<ContentRevisionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'content_type',
        'content_id',
        'changes',
        'summary',
        'drafted_by',
        'status',
        'decided_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'changes' => 'array',
        'status' => RevisionStatus::class,
        'decided_at' => 'datetime',
    ];

    /** @param  Builder<static>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', RevisionStatus::Pending);
    }
}
