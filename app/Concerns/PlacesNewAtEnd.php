<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * À la création, un ordre laissé à 0 (ou vide) place l'élément en dernier : il prend le rang
 * qui suit le plus grand ordre existant. Un ordre choisi explicitement est gardé.
 */
trait PlacesNewAtEnd
{
    public static function bootPlacesNewAtEnd(): void
    {
        static::creating(function (Model $model): void {
            if (! empty($model->getAttribute('sort_order'))) {
                return;
            }

            $model->setAttribute('sort_order', ((int) $model->newQuery()->max('sort_order')) + 1);
        });
    }
}
