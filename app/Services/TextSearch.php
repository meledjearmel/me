<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Recherche plein texte en PHP, sans accents ni casse, pour les champs traduits stockés en
 * JSON que SQL compare mal. Une recherche garde un texte qui contient tous ses termes.
 */
class TextSearch
{
    private const int MIN_LENGTH = 2;

    /**
     * Termes de la recherche ; vide quand elle est trop courte pour filtrer.
     *
     * @return list<string>
     */
    public function terms(string $query): array
    {
        $normalized = $this->normalize($query);

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            return [];
        }

        return array_values(array_filter(explode(' ', $normalized)));
    }

    /**
     * @param  list<string>  $terms
     * @param  iterable<string|null>  $texts
     */
    public function matches(array $terms, iterable $texts): bool
    {
        $haystack = $this->normalize(implode(' ', array_filter([...$texts])));

        return collect($terms)->every(fn (string $term): bool => str_contains($haystack, $term));
    }

    private function normalize(string $text): string
    {
        return Str::of($text)->stripTags()->ascii()->lower()->squish()->toString();
    }
}
