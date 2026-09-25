<?php

namespace App\Concerns;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Donne à un modèle un statut de publication (brouillon ou publié) et la portée `published()`
 * que les pages publiques et le CV utilisent pour ne montrer que le contenu publié.
 */
trait HasPublicationStatus
{
    public function initializeHasPublicationStatus(): void
    {
        $this->mergeFillable(['status']);
        $this->mergeCasts(['status' => PublicationStatus::class]);
    }

    /** @param  Builder<static>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), PublicationStatus::Published);
    }
}
