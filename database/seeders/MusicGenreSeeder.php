<?php

namespace Database\Seeders;

use App\Models\MusicGenre;
use Illuminate\Database\Seeder;

class MusicGenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $genres = (app()->environment('local')) ?
            [
                ['key' => 'lofi', 'label' => ['fr' => 'Lo-fi', 'en' => 'Lo-fi']],
                ['key' => 'jazz', 'label' => ['fr' => 'Jazz', 'en' => 'Jazz']],
                ['key' => 'afrobeat', 'label' => ['fr' => 'Afrobeat', 'en' => 'Afrobeat']],
                ['key' => 'ambient', 'label' => ['fr' => 'Ambiance', 'en' => 'Ambient']],
            ] : [
                ['key' => 'default', 'label' => ['fr' => 'Défaut', 'en' => 'Default']],
            ];

        foreach ($genres as $position => $genre) {
            MusicGenre::query()->updateOrCreate(
                ['key' => $genre['key']],
                ['label' => $genre['label'], 'sort_order' => $position + 1],
            );
        }
    }
}
