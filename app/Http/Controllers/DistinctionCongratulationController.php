<?php

namespace App\Http\Controllers;

use App\Enums\CongratulationSource;
use App\Models\Congratulation;
use App\Models\Counter;
use App\Models\Distinction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistinctionCongratulationController extends Controller
{
    /**
     * Ajoute les félicitations d'un visiteur à une distinction de la page À propos (les clics
     * sont regroupés côté navigateur). Le compteur global du tableau de bord suit aussi.
     */
    public function __invoke(Request $request, string $locale, Distinction $distinction): JsonResponse
    {
        abort_unless(Distinction::query()->published()->whereKey($distinction->id)->exists(), 404);

        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:'.CongratulationController::MAX_PER_REQUEST],
        ]);

        $distinction->increment('congratulations_count', $validated['count']);
        Counter::add(Counter::CONGRATULATIONS, $validated['count']);
        Congratulation::record(CongratulationSource::About, $validated['count'], distinction: $distinction);

        return response()->json(['total' => $distinction->congratulations_count]);
    }
}
