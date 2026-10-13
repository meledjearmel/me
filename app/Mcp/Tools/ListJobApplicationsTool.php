<?php

namespace App\Mcp\Tools;

use App\Models\JobApplication;
use App\Services\Ai\CvTailor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-job-applications')]
#[Description('Liste les pages de candidature (une par entreprise), les plus récentes d\'abord : entreprise, poste, statut, lien, projets montrés, consultations, CV téléchargés, première et dernière visite. Avec un id : la page complète (mot d\'accompagnement, offre, notes), son CV adapté, de quoi l\'adapter (cv_material : expériences avec leurs réalisations et compétences, avec identifiants) et ses derniers événements.')]
#[IsReadOnly]
class ListJobApplicationsTool extends Tool
{
    public function handle(Request $request, CvTailor $tailor): Response
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (isset($data['id'])) {
            $application = JobApplication::query()->with('projects:id')->find($data['id']);

            if ($application === null) {
                return Response::error('Page de candidature introuvable.');
            }

            return Response::json([
                ...$this->summary($application),
                'intro' => $application->intro,
                'job_posting_url' => $application->job_posting_url,
                'job_posting_text' => $application->job_posting_text,
                'notes' => $application->notes,
                'cv_tailoring' => $application->cv_tailoring,
                // De quoi adapter le CV avec save-job-application (identifiants des réalisations et des compétences).
                'cv_material' => collect($tailor->material($application->locale))->except('text')->all(),
                'events' => $application->events()->latest('created_at')->limit(30)->get()
                    ->map(fn ($event): array => $event->only(['type', 'country', 'city', 'device', 'created_at']))->all(),
            ]);
        }

        return Response::json(JobApplication::query()->with('projects:id')->latest()->limit($data['limit'] ?? 15)->get()
            ->map(fn (JobApplication $application): array => $this->summary($application))->all());
    }

    /** @return array<string, mixed> */
    private function summary(JobApplication $application): array
    {
        return [
            'id' => $application->id,
            'company' => $application->company,
            'contact_name' => $application->contact_name,
            'position' => $application->position,
            'locale' => $application->locale,
            'status' => $application->status->value,
            'url' => route('job-applications.show', ['locale' => $application->locale, 'token' => $application->token]),
            'job_profile_id' => $application->job_profile_id,
            'project_ids' => $application->projects->modelKeys(),
            'views_count' => $application->views_count,
            'cv_downloads_count' => $application->cv_downloads_count,
            'first_viewed_at' => $application->first_viewed_at?->toIso8601String(),
            'last_viewed_at' => $application->last_viewed_at?->toIso8601String(),
            'admin_url' => route('admin.job-applications.show', $application),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Une seule page, en entier.'),
            'limit' => $schema->integer()->description('Nombre de pages listées (15 par défaut, 50 max.).'),
        ];
    }
}
