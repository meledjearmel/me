<?php

namespace App\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Recherche, filtres et pagination communs aux listes de l'administration.
 * L'état (recherche, filtres, page, taille) vit dans l'URL pour qu'un rechargement ou un lien le retrouve.
 */
trait PaginatesAdminLists
{
    /** @var list<int> */
    private const array PER_PAGE_OPTIONS = [10, 25, 50];

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $searchable  colonnes recherchées (ex. « name » ou « title->fr » pour un champ traduisible)
     * @param  list<string>  $filterable  colonnes filtrables par égalité stricte
     * @return LengthAwarePaginator<int, *>
     */
    protected function paginateList(Builder $query, Request $request, array $searchable = [], array $filterable = []): LengthAwarePaginator
    {
        $term = trim((string) $request->query('search', ''));

        if ($term !== '' && $searchable !== []) {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(function (BuilderContract $group) use ($searchable, $like): void {
                foreach ($searchable as $column) {
                    $group->orWhere($column, 'like', $like);
                }
            });
        }

        foreach ($filterable as $column) {
            $value = $request->query($column);

            if (is_string($value) && $value !== '') {
                $query->where($column, $value);
            }
        }

        return $query->paginate($this->perPage($request))->withQueryString();
    }

    /**
     * Les valeurs de recherche/filtre à renvoyer à la page pour préremplir la barre d'outils.
     *
     * @param  list<string>  $filterable
     * @return array<string, string|int>
     */
    protected function listFilters(Request $request, array $filterable = []): array
    {
        $filters = ['search' => trim((string) $request->query('search', '')), 'per_page' => $this->perPage($request)];

        foreach ($filterable as $column) {
            $value = $request->query($column);
            $filters[$column] = is_string($value) ? $value : '';
        }

        return $filters;
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::PER_PAGE_OPTIONS[0]);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[0];
    }
}
