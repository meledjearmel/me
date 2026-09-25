<?php

namespace App\Http\Controllers;

use App\Services\DashboardReport;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tableau de bord de l'administration.
 */
class DashboardController extends Controller
{
    public function __invoke(DashboardReport $report): Response
    {
        return Inertia::render('dashboard', $report->toArray());
    }
}
