<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Réglages d'affichage du site public, sur une seule ligne. */
class SiteSetting extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'contact_opens_drawer',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'contact_opens_drawer' => 'boolean',
    ];

    /** Les réglages, créés avec leurs valeurs par défaut au premier accès. */
    public static function current(): self
    {
        // refresh() : une ligne tout juste créée récupère les valeurs par défaut de la base.
        return static::query()->first() ?? static::query()->create()->refresh();
    }
}
