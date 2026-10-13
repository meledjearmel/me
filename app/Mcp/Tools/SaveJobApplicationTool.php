<?php

namespace App\Mcp\Tools;

use App\Enums\JobApplicationStatus;
use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Mcp\Concerns\NamesAiClient;
use App\Models\JobApplication;
use App\Services\Ai\JobApplicationDrafter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('save-job-application')]
#[Description('Prépare une page de candidature en brouillon (sans id), ou modifie un brouillon (avec id, seulement les champs envoyés) : entreprise, poste, recruteur, langue, mot d\'accompagnement, offre d\'emploi, projets montrés (dans l\'ordre) et profil métier du CV. Lis l\'offre avec read-web-page et le portfolio avec get-catalog et get-project ; n\'invente aucune expérience. Le lien ne s\'active jamais ici : le développeur relit et met la page en ligne dans l\'administration.')]
class SaveJobApplicationTool extends Tool
{
    use NamesAiClient;

    public function handle(Request $request, JobApplicationDrafter $drafter): Response
    {
        $creating = $request->get('id') === null;
        $required = $creating ? 'required' : 'sometimes';

        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'company' => [$required, 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'position' => [$required, 'string', 'max:255'],
            'locale' => [$required, Rule::in(['fr', 'en'])],
            'intro' => [$required, 'string', 'max:5000'],
            'job_posting_url' => ['nullable', 'url:http,https', 'max:2000'],
            'job_posting_text' => ['nullable', 'string', 'max:20000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'job_profile_id' => ['nullable', 'integer', Rule::exists('job_profiles', 'id')->where('status', PublicationStatus::Published->value)],
            'project_ids' => ['nullable', 'array', 'max:6'],
            'project_ids.*' => ['integer', 'distinct', Rule::exists('projects', 'id')->where('status', ProjectStatus::Published->value)->whereNull('deleted_at')],
        ]);

        $application = $creating
            ? new JobApplication(['status' => JobApplicationStatus::Draft])
            : JobApplication::query()->find($data['id']);

        if ($application === null) {
            return Response::error('Page de candidature introuvable.');
        }

        if (! $creating && $application->status !== JobApplicationStatus::Draft) {
            return Response::error('Cette page est déjà en ligne ou archivée : modifie-la dans l\'administration ('.route('admin.job-applications.edit', $application).').');
        }

        // Les mentions inconnues redeviennent leur nom, les identifiants égarés disparaissent.
        if (isset($data['intro'])) {
            $data['intro'] = $drafter->cleanIntro($data['intro']);
        }

        $application->fill(collect($data)->except(['id', 'project_ids'])->all());
        $application->drafted_by = $this->aiClient($request);
        $application->save();

        if (array_key_exists('project_ids', $data)) {
            $application->syncProjects($data['project_ids'] ?? []);
        }

        return Response::text(($creating ? 'Brouillon de candidature créé' : 'Brouillon mis à jour')
            ." pour {$application->company} : à relire et mettre en ligne dans l'administration (".route('admin.job-applications.edit', $application).'). Lien une fois en ligne : '
            .route('job-applications.show', ['locale' => $application->locale, 'token' => $application->token]));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Brouillon à modifier (voir list-job-applications) ; absent pour en créer un.'),
            'company' => $schema->string()->description('Entreprise (sert aussi à l\'adresse de la page).'),
            'contact_name' => $schema->string()->description('Recruteur ou destinataire, s\'il est connu.'),
            'position' => $schema->string()->description('Intitulé du poste.'),
            'locale' => $schema->string()->enum(['fr', 'en'])->description('Langue de la page et du CV : celle de l\'offre.'),
            'intro' => $schema->string()->description('Mot d\'accompagnement à la première personne, 120 à 220 mots, 2 ou 3 paragraphes séparés par une ligne vide, texte brut : pourquoi ce poste, ce que j\'apporte de concret (projets réels), invitation à échanger. Pour citer un projet, insère son jeton de mention tel que get-catalog le donne (ex. « @[App Station](project:12) ») : il s\'affiche « App Station » en lien vers le projet, avec une carte au survol. N\'écris jamais d\'identifiant seul dans le texte (pas de « (id 12) »).'),
            'job_posting_url' => $schema->string()->description('Adresse de l\'offre.'),
            'job_posting_text' => $schema->string()->description('Texte de l\'offre, pour s\'y référer.'),
            'notes' => $schema->string()->description('Notes privées pour le développeur (points à vérifier…), jamais affichées.'),
            'job_profile_id' => $schema->integer()->description('Profil métier dont le CV est proposé (job_profiles de get-catalog).'),
            'project_ids' => $schema->array()->items($schema->integer())->description('2 à 4 projets publiés (get-catalog), du plus pertinent au moins pertinent.'),
        ];
    }
}
