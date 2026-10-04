<?php

namespace App\Http\Controllers;

use App\Enums\UsesCategory;
use App\Models\UsesItem;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Page « Uses » : le matériel et les outils du quotidien, par rubrique.
 * Tant qu'elle est vide, elle n'existe pas (404).
 */
class UsesController extends Controller
{
    public function index(string $locale): Response
    {
        $items = UsesItem::query()->orderBy('sort_order')->orderBy('name')->get();

        abort_if($items->isEmpty(), HttpResponse::HTTP_NOT_FOUND);

        $byCategory = $items->groupBy(fn (UsesItem $item): string => $item->category->value);

        return Inertia::render('public/uses', [
            'categories' => collect(UsesCategory::cases())
                ->filter(fn (UsesCategory $category): bool => $byCategory->has($category->value))
                ->map(fn (UsesCategory $category): array => [
                    'key' => $category->value,
                    'items' => $byCategory[$category->value]->map(fn (UsesItem $item): array => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->getTranslation('description', $locale) ?: null,
                        'url' => $item->url,
                    ])->values(),
                ])
                ->values(),
        ]);
    }
}
