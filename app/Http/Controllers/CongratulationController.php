<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CongratulationController extends Controller
{
    /** Nombre maximal de félicitations acceptées par requête (les clics sont regroupés côté navigateur). */
    public const MAX_PER_REQUEST = 25;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:'.self::MAX_PER_REQUEST],
        ]);

        return response()->json([
            'total' => Counter::add(Counter::CONGRATULATIONS, $validated['count']),
        ]);
    }
}
