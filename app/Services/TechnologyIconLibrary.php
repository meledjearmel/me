<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Bibliothèque des logos de technologies (public/icons/tech) : liste des logos
 * disponibles, résolution des URL par thème, et import depuis le catalogue
 * public Iconify (Logos, Devicon, Simple Icons).
 *
 * Un logo `{slug}` peut avoir des variantes `{slug}-light` et `{slug}-dark`
 * (logo adapté à un thème), en svg ou en png.
 */
class TechnologyIconLibrary
{
    private const API_URL = 'https://api.iconify.design';

    /** Collections Iconify proposées : logos de marques, en couleur ou monochromes. */
    public const COLLECTIONS = ['logos', 'devicon', 'simple-icons'];

    public const THEMES = ['light', 'dark'];

    private const EXTENSIONS = ['svg', 'png'];

    private const MAX_SVG_BYTES = 200_000;

    private const LIGHT_THEME_COLOR = '#18181b';

    private const DARK_THEME_COLOR = '#fafafa';

    private readonly string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? public_path('icons/tech');
    }

    /**
     * Logos disponibles (une entrée par slug, variantes de thème regroupées).
     *
     * @return list<array{slug: string, light_url: string|null, dark_url: string|null}>
     */
    public function all(): array
    {
        $slugs = [];

        foreach (self::EXTENSIONS as $extension) {
            foreach (File::glob("{$this->directory}/*.{$extension}") ?: [] as $path) {
                $slugs[] = preg_replace('/-(light|dark)$/', '', pathinfo($path, PATHINFO_FILENAME));
            }
        }

        $slugs = array_values(array_unique($slugs));
        sort($slugs);

        return array_map(fn (string $slug): array => [
            'slug' => $slug,
            'light_url' => $this->url($slug, 'light'),
            'dark_url' => $this->url($slug, 'dark'),
        ], $slugs);
    }

    /**
     * Sans thème : le logo existe sous une forme quelconque. Avec un thème :
     * le fichier propre à ce thème (`{slug}-{theme}`) existe.
     */
    public function exists(string $slug, ?string $theme = null): bool
    {
        if ($theme === null) {
            return $this->url($slug, 'light') !== null || $this->url($slug, 'dark') !== null;
        }

        foreach (self::EXTENSIONS as $extension) {
            if (is_file("{$this->directory}/{$slug}-{$theme}.{$extension}")) {
                return true;
            }
        }

        return false;
    }

    /**
     * URL du logo pour un thème : `{slug}-{theme}` s'il existe, sinon `{slug}`,
     * sinon null. La date du fichier dans l'URL évite qu'un logo remplacé soit
     * servi depuis l'ancien cache du navigateur.
     */
    public function url(?string $slug, string $theme): ?string
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        foreach (["{$slug}-{$theme}", $slug] as $file) {
            foreach (self::EXTENSIONS as $extension) {
                $path = "{$this->directory}/{$file}.{$extension}";

                if (is_file($path)) {
                    return asset("icons/tech/{$file}.{$extension}").'?v='.filemtime($path);
                }
            }
        }

        return null;
    }

    /**
     * Recherche dans le catalogue Iconify.
     *
     * @return list<array{id: string, name: string, collection: string, preview_url: string}>|null Null si le catalogue est injoignable.
     */
    public function search(string $query): ?array
    {
        try {
            $response = Http::timeout(10)->acceptJson()->get(self::API_URL.'/search', [
                'query' => $query,
                'limit' => 48,
                'prefixes' => implode(',', self::COLLECTIONS),
            ]);
        } catch (Throwable) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $results = [];

        foreach ((array) $response->json('icons', []) as $id) {
            if (! is_string($id) || ! $this->isCatalogId($id)) {
                continue;
            }

            [$collection, $name] = explode(':', $id, 2);

            $results[] = [
                'id' => $id,
                'name' => $name,
                'collection' => $collection,
                'preview_url' => self::API_URL."/{$collection}/{$name}.svg",
            ];
        }

        return $results;
    }

    /**
     * Télécharge un logo du catalogue vers la bibliothèque (voir `store()`).
     *
     * @param  'light'|'dark'|null  $theme
     * @return bool False si le logo est introuvable, trop gros ou suspect.
     */
    public function import(string $catalogId, string $slug, ?string $theme = null): bool
    {
        if (! $this->isCatalogId($catalogId)) {
            return false;
        }

        [$collection, $name] = explode(':', $catalogId, 2);

        try {
            $response = Http::timeout(10)->get(self::API_URL."/{$collection}/{$name}.svg");
        } catch (Throwable) {
            return false;
        }

        if ($response->failed()) {
            return false;
        }

        return $this->store($response->body(), $slug, $theme);
    }

    /**
     * Enregistre un SVG dans la bibliothèque. Sans thème, un logo monochrome
     * (currentColor) est enregistré en deux variantes `-light` et `-dark` pour
     * rester lisible sur les deux thèmes, un logo en couleur en un seul fichier.
     * Avec un thème, seule la variante de ce thème est écrite.
     *
     * @param  'light'|'dark'|null  $theme
     * @return bool False si le SVG est invalide, trop gros ou suspect.
     */
    public function store(string $svg, string $slug, ?string $theme = null): bool
    {
        if (! $this->isSafeSvg($svg)) {
            return false;
        }

        File::ensureDirectoryExists($this->directory);

        if (str_contains($svg, 'currentColor')) {
            $colors = ['light' => self::LIGHT_THEME_COLOR, 'dark' => self::DARK_THEME_COLOR];

            foreach ($theme === null ? $colors : [$theme => $colors[$theme]] as $variant => $color) {
                File::put("{$this->directory}/{$slug}-{$variant}.svg", str_replace('currentColor', $color, $svg));
            }
        } else {
            $file = $theme === null ? $slug : "{$slug}-{$theme}";

            File::put("{$this->directory}/{$file}.svg", $svg);
        }

        return true;
    }

    private function isCatalogId(string $id): bool
    {
        return preg_match('/^('.implode('|', self::COLLECTIONS).'):[a-z0-9-]+$/', $id) === 1;
    }

    /**
     * Le logo est servi via <img> (scripts inertes), mais on refuse tout SVG
     * qui contient du script ou des déclarations d'entités. Un prologue XML, un
     * doctype ou des commentaires avant la balise <svg> sont admis (exports
     * d'Inkscape, Illustrator…).
     */
    private function isSafeSvg(string $svg): bool
    {
        return strlen($svg) <= self::MAX_SVG_BYTES
            && preg_match('/^\s*(<\?xml[^>]*\?>\s*)?(<!--.*?-->\s*)*(<!DOCTYPE[^>\[]*>\s*)?(<!--.*?-->\s*)*<svg[\s>]/is', $svg) === 1
            && preg_match('/<script|<!ENTITY|\son\w+\s*=|javascript:/i', $svg) !== 1;
    }
}
