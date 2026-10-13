<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EstimatorConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Applique d'un coup une configuration de l'estimateur relue dans l'administration (la
 * proposition de l'IA) : réglages et éléments, actifs dès leur création.
 */
class EstimatorConfigurationController extends Controller
{
    public function store(Request $request, EstimatorConfigurator $configurator): RedirectResponse
    {
        $result = $configurator->apply($request->only(['settings', 'items']), activateNew: true);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':created élément(s) créé(s), :updated mis à jour.', [
            'created' => count($result['created']),
            'updated' => count($result['updated']),
        ])]);

        return back();
    }
}
