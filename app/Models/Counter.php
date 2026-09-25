<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Counter extends Model
{
    public const CONGRATULATIONS = 'congratulations';

    /** @var list<string> */
    protected $fillable = ['key', 'total'];

    /** Valeur actuelle d'un compteur (0 s'il n'existe pas encore). */
    public static function total(string $key): int
    {
        return (int) static::query()->where('key', $key)->value('total');
    }

    /**
     * Ajoute `$by` au compteur en une seule requête atomique : deux visiteurs
     * simultanés ne peuvent pas s'écraser l'un l'autre.
     */
    public static function add(string $key, int $by): int
    {
        $by = max(0, $by);
        $now = now();

        DB::table('counters')->upsert(
            [['key' => $key, 'total' => $by, 'created_at' => $now, 'updated_at' => $now]],
            ['key'],
            ['total' => DB::raw('total + '.$by), 'updated_at' => $now],
        );

        return static::total($key);
    }
}
