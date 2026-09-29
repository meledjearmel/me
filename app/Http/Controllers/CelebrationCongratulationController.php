<?php

namespace App\Http\Controllers;

use App\Enums\CongratulationSource;
use App\Models\Celebration;
use App\Models\Congratulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CelebrationCongratulationController extends Controller
{
    /**
     * Ajoute les félicitations d'un visiteur à une surprise (les clics sont
     * regroupés côté navigateur, comme pour le compteur de la page À propos).
     */
    public function __invoke(Request $request, string $locale, Celebration $celebration): JsonResponse
    {
        abort_unless(Celebration::query()->showable()->whereKey($celebration->id)->exists(), 404);

        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:'.CongratulationController::MAX_PER_REQUEST],
        ]);

        $celebration->increment('congratulations_count', $validated['count']);
        Congratulation::record(CongratulationSource::Surprise, $validated['count'], $celebration);

        return response()->json(['total' => $celebration->congratulations_count]);
    }
}
