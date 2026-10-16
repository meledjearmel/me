<?php

namespace App\Mcp\Tools;

use App\Enums\ProjectStatus;
use App\Mcp\Concerns\NamesAiClient;
use App\Mcp\Concerns\ReceivesImages;
use App\Models\Project;
use App\Services\Ai\ContentRevisions;
use App\Services\PublicUrlFetcher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

#[Name('add-project-image')]
#[Description('Ajoute une image à un projet : sa couverture (role « cover », remplace l\'ancienne, affichée en 16:9 sur les cartes et en tête de l\'étude de cas) ou une image de sa galerie (role « gallery », ajoutée à la suite). Sur un brouillon, l\'image est ajoutée tout de suite ; sur un projet publié, elle est proposée comme une révision (summary obligatoire) que le développeur applique ou rejette, et rien ne change sur le site avant. Source : une adresse publique (url) ou un fichier local encodé en base64 (data), JPEG, PNG, WebP ou GIF de 5 Mo au plus. De préférence de vraies captures du projet ; jamais d\'image d\'un client sans son accord.')]
class AddProjectImageTool extends Tool
{
    use NamesAiClient, ReceivesImages;

    public function handle(Request $request, PublicUrlFetcher $fetcher, ContentRevisions $revisions): Response
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'role' => ['required', 'in:cover,gallery'],
            'summary' => ['nullable', 'string', 'max:1000'],
            ...$this->imageRules(),
        ]);

        $project = Project::query()->find($request->get('id'));

        if ($project === null) {
            return Response::error('Projet introuvable.');
        }

        $isPublished = $project->status !== ProjectStatus::Draft;

        if ($isPublished && blank($request->get('summary'))) {
            return Response::error('Projet publié : l\'image sera proposée comme une révision, décris-la dans summary (ce qu\'elle montre et pourquoi).');
        }

        try {
            ['bytes' => $bytes, 'extension' => $extension] = $this->receiveImage($request, $fetcher);
        } catch (RuntimeException $exception) {
            return Response::error($exception->getMessage());
        }

        $role = $request->get('role');

        if ($isPublished) {
            $revision = $revisions->proposeImage($project, $role, $bytes, $extension, $request->get('summary'), $this->aiClient($request));

            return Response::text("Révision #{$revision->id} proposée ; rien n'a changé sur le site. Le développeur la relit et l'applique ici : ".route('admin.revisions.show', $revision));
        }

        $project->addMediaFromString($bytes)
            ->usingFileName("{$project->slug}-{$role}-".Str::random(6).".{$extension}")
            ->toMediaCollection($role);

        return Response::text($role === 'cover'
            ? "Couverture du brouillon #{$project->id} enregistrée."
            : "Image ajoutée à la galerie du brouillon #{$project->id} (".$project->getMedia('gallery')->count().' au total).');
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant du projet (brouillon ou publié).')->required(),
            'role' => $schema->string()->enum(['cover', 'gallery'])->description('cover : couverture ; gallery : image de la galerie.')->required(),
            ...$this->imageFields($schema),
            'summary' => $schema->string()->description('Projet publié uniquement (obligatoire) : ce que montre l\'image et pourquoi l\'ajouter, en une à trois phrases, pour le développeur.'),
        ];
    }
}
