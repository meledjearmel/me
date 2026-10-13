<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Services\Ai\CvTailor;
use App\Services\CvGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

/**
 * Le CV d'une candidature, depuis sa fiche : le voir, le faire adapter à l'offre par l'IA,
 * retoucher le titre et le résumé, ou revenir au CV standard du profil.
 */
class JobApplicationCvController extends Controller
{
    /** Le CV tel que l'entreprise le téléchargera, ouvert dans le navigateur. */
    public function show(JobApplication $jobApplication, CvGenerator $cv): Response
    {
        abort_if($jobApplication->jobProfile === null, 404);

        return response($cv->forApplication($jobApplication), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$cv->filename($jobApplication->jobProfile).'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function tailor(JobApplication $jobApplication, CvTailor $tailor): RedirectResponse
    {
        abort_if($jobApplication->jobProfile === null, 404);

        Inertia::flash('toast', $tailor->tailor($jobApplication)
            ? ['type' => 'success', 'message' => __('CV adapté à l\'offre : relisez-le avant d\'envoyer le lien.')]
            : ['type' => 'error', 'message' => __('L\'assistance IA est momentanément indisponible.')]);

        return back();
    }

    /** Retouche du titre et du résumé du CV adapté (le reste de l'adaptation est gardé). */
    public function update(Request $request, JobApplication $jobApplication, CvTailor $tailor): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:1500'],
        ]);

        $tailor->save($jobApplication, [...($jobApplication->cv_tailoring ?? []), ...$data]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('CV adapté mis à jour.')]);

        return back();
    }

    public function destroy(JobApplication $jobApplication): RedirectResponse
    {
        $jobApplication->forceFill(['cv_tailoring' => null, 'cv_tailored_at' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('La page propose de nouveau le CV standard.')]);

        return back();
    }
}
