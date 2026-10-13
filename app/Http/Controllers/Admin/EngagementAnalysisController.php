<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Engagement;
use App\Services\Ai\EstimateAssistant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/** « Analyser avec l'IA » sur la fiche d'une demande : risques, points flous, questions, heures réalistes. */
class EngagementAnalysisController extends Controller
{
    public function store(Engagement $engagement, EstimateAssistant $assistant): RedirectResponse
    {
        Inertia::flash('toast', $assistant->analyze($engagement)
            ? ['type' => 'success', 'message' => __('Analyse de l\'IA mise à jour.')]
            : ['type' => 'error', 'message' => __('L\'assistance IA est momentanément indisponible.')]);

        return back();
    }
}
