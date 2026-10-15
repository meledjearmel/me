<?php

namespace App\Mcp\Tools;

use App\Models\AppRelease;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('publish-app-release')]
#[Description('Publie un build de l\'application mobile dont l\'APK a été envoyé : il devient la dernière version, proposée en mise à jour par l\'app installée. Seulement sur demande explicite du développeur. Un build plus ancien que la dernière version publiée est refusé.')]
class PublishAppReleaseTool extends Tool
{
    public function handle(Request $request): Response
    {
        $request->validate(['id' => ['required', 'integer']]);

        $release = AppRelease::query()->find($request->get('id'));

        if ($release === null) {
            return Response::error('Build introuvable.');
        }

        if ($release->isPublished()) {
            return Response::text("Le build {$release->version_name} ({$release->build_number}) est déjà publié.");
        }

        if (! $release->hasFile()) {
            return Response::error('L\'APK de ce build n\'a pas encore été envoyé.');
        }

        $latest = AppRelease::latestPublished();

        if ($latest !== null && $latest->build_number > $release->build_number) {
            return Response::error("La version publiée ({$latest->version_name}, build {$latest->build_number}) est plus récente.");
        }

        $release->forceFill(['published_at' => now()])->save();

        return Response::text("Version {$release->version_name} (build {$release->build_number}) publiée : l'app la proposera au prochain démarrage.");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant du build, renvoyé par prepare-app-release.')->required(),
        ];
    }
}
