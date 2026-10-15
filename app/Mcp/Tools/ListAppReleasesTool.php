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
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-app-releases')]
#[Description('Liste les builds de l\'application mobile, du plus récent au plus ancien (version, build, notes, APK envoyé ou non, publié ou non), et donne le prochain build_number à utiliser.')]
#[IsReadOnly]
class ListAppReleasesTool extends Tool
{
    public function handle(Request $request): Response
    {
        $request->validate(['limit' => ['nullable', 'integer', 'min:1', 'max:50']]);

        $releases = AppRelease::query()
            ->orderByDesc('build_number')
            ->limit($request->get('limit') ?? 10)
            ->get();

        return Response::json([
            'latest_published' => AppRelease::latestPublished()?->summary(),
            'next_build_number' => AppRelease::highestBuildNumber() + 1,
            'releases' => $releases->map(fn (AppRelease $release): array => $release->summary())->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Nombre de builds à lister (10 par défaut).'),
        ];
    }
}
