<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WeeklyReportMail;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Services\WeeklyReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le rapport de la semaine : l'activer ou le couper, voir le mail tel qu'il partira lundi,
 * et se l'envoyer tout de suite.
 */
class WeeklyReportController extends Controller
{
    public function show(): Response
    {
        $settings = SiteSetting::current();

        return Inertia::render('admin/weekly-report/show', [
            'enabled' => $settings->weekly_report_enabled,
            'sentAt' => $settings->weekly_report_sent_at?->toIso8601String(),
            'recipient' => Profile::query()->value('email'),
        ]);
    }

    /** L'aperçu du mail (sans « À retenir », rédigé par l'IA à l'envoi), affiché dans un cadre. */
    public function preview(WeeklyReport $report): HttpResponse
    {
        return response((new WeeklyReportMail($report->build(withHighlights: false)))->render());
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(['weekly_report_enabled' => ['required', 'boolean']]);

        SiteSetting::current()->update(['weekly_report_enabled' => $request->boolean('weekly_report_enabled')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $request->boolean('weekly_report_enabled')
            ? __('Rapport activé : il partira chaque lundi matin.')
            : __('Rapport désactivé.')]);

        return back();
    }

    public function send(WeeklyReport $report): RedirectResponse
    {
        $status = $report->send(force: true);

        Inertia::flash('toast', $status === 'sent'
            ? ['type' => 'success', 'message' => __('Rapport envoyé.')]
            : ['type' => 'error', 'message' => __('Aucun email sur le profil : rapport non envoyé.')]);

        return back();
    }
}
