<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JobApplicationResource;
use App\Models\JobApplication;
use App\Services\Ai\CvTailor;
use App\Services\CvGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @tags Candidatures
 */
class JobApplicationCvController extends Controller
{
    /**
     * CV d'une candidature (PDF)
     *
     * Le CV que l'entreprise téléchargera : adapté à l'offre s'il l'a été, sinon le CV
     * standard du profil métier choisi. 404 sans profil métier.
     */
    public function show(JobApplication $jobApplication, CvGenerator $cv): Response
    {
        abort_if($jobApplication->jobProfile === null, 404);

        return response($cv->forApplication($jobApplication), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$cv->filename($jobApplication->jobProfile).'"',
        ]);
    }

    /**
     * Adapter le CV à l'offre avec l'IA
     *
     * L'IA réécrit le titre et le résumé d'après le parcours réel, choisit et ordonne les
     * réalisations parmi celles du site et met des compétences en avant (`cv_tailoring`).
     * Elle s'appuie sur le texte de l'offre (`job_posting_text`), sinon sur le poste.
     *
     * @response 503 array{message: string}
     */
    public function tailor(JobApplication $jobApplication, CvTailor $tailor): JobApplicationResource|JsonResponse
    {
        abort_if($jobApplication->jobProfile === null, 404);

        if (! $tailor->tailor($jobApplication)) {
            return response()->json(['message' => __('L\'assistance IA est momentanément indisponible.')], 503);
        }

        return new JobApplicationResource($jobApplication->refresh()->load('projects:id'));
    }

    /**
     * Retoucher le CV adapté
     *
     * Remplace le titre et/ou le résumé du CV adapté ; le choix des réalisations et des
     * compétences est gardé.
     */
    public function update(Request $request, JobApplication $jobApplication, CvTailor $tailor): JobApplicationResource
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:1500'],
        ]);

        $tailor->save($jobApplication, [...($jobApplication->cv_tailoring ?? []), ...$data]);

        return new JobApplicationResource($jobApplication->refresh()->load('projects:id'));
    }

    /**
     * Revenir au CV standard
     */
    public function destroy(JobApplication $jobApplication): JobApplicationResource
    {
        $jobApplication->forceFill(['cv_tailoring' => null, 'cv_tailored_at' => null])->save();

        return new JobApplicationResource($jobApplication->load('projects:id'));
    }
}
