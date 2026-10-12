<?php

namespace App\Mcp\Tools;

use App\Enums\ProjectStatus;
use App\Mcp\Concerns\ReceivesImages;
use App\Models\Project;
use App\Services\PublicUrlFetcher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

#[Description('Ajoute une image à un brouillon de projet : sa couverture (role « cover », remplace l\'ancienne, affichée en 16:9 sur les cartes et en tête de l\'étude de cas) ou une image de sa galerie (role « gallery », ajoutée à la suite). Source : une adresse publique (url) ou un fichier local encodé en base64 (data), JPEG, PNG, WebP ou GIF de 5 Mo au plus. De préférence de vraies captures du projet ; jamais d\'image d\'un client sans son accord.')]
class AddProjectImageTool extends Tool
{
    use ReceivesImages;

    public function handle(Request $request, PublicUrlFetcher $fetcher): Response
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'role' => ['required', 'in:cover,gallery'],
            ...$this->imageRules(),
        ]);

        $project = Project::query()->find($request->get('id'));

        if ($project?->status !== ProjectStatus::Draft) {
            return Response::error('Seul un brouillon de projet existant peut recevoir des images.');
        }

        try {
            ['bytes' => $bytes, 'extension' => $extension] = $this->receiveImage($request, $fetcher);
        } catch (RuntimeException $exception) {
            return Response::error($exception->getMessage());
        }

        $role = $request->get('role');
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
            'id' => $schema->integer()->description('Identifiant du brouillon de projet.')->required(),
            'role' => $schema->string()->enum(['cover', 'gallery'])->description('cover : couverture ; gallery : image de la galerie.')->required(),
            ...$this->imageFields($schema),
        ];
    }
}
