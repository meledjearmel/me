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

#[Name('prepare-app-release')]
#[Description('Prépare l\'envoi d\'un build Android (APK) de l\'application mobile du développeur : enregistre sa version (version_name et build_number de pubspec.yaml, « 1.4.0+12 » donne 1.4.0 et 12) et ses notes de version, puis renvoie une adresse d\'envoi signée, valable 15 minutes. Envoie ensuite le fichier avec la commande curl renvoyée (corps brut, PUT), depuis la machine où l\'APK a été compilé (flutter build apk --release). Le build_number doit dépasser celui de tous les builds existants. Le build reste privé et non publié : publish-app-release le publie, sur demande du développeur. Rappeler au même build_number un build pas encore publié renvoie une nouvelle adresse (fichier remplacé).')]
class PrepareAppReleaseTool extends Tool
{
    public function handle(Request $request): Response
    {
        $request->validate([
            'version_name' => ['required', 'string', 'max:50', 'regex:/^\d+\.\d+\.\d+([-.][0-9A-Za-z.-]+)?$/'],
            'build_number' => ['required', 'integer', 'min:1', 'max:2100000000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'sha256' => ['nullable', 'string', 'regex:/^[A-Fa-f0-9]{64}$/'],
        ]);

        $buildNumber = (int) $request->get('build_number');
        $release = AppRelease::query()->firstWhere('build_number', $buildNumber);

        if ($release?->isPublished()) {
            return Response::error("Le build {$buildNumber} est déjà publié : augmente le build_number (pubspec.yaml) et recompile.");
        }

        if ($release === null && $buildNumber <= AppRelease::highestBuildNumber()) {
            return Response::error('build_number trop petit : le dernier build est le '.AppRelease::highestBuildNumber().'. Augmente-le dans pubspec.yaml et recompile.');
        }

        $release ??= new AppRelease(['build_number' => $buildNumber]);
        $release->fill([
            'version_name' => $request->get('version_name'),
            'notes' => $request->get('notes'),
            'expected_sha256' => $request->get('sha256') !== null ? strtolower($request->get('sha256')) : null,
        ])->save();

        $url = $release->uploadUrl();

        return Response::json([
            'id' => $release->id,
            'version_name' => $release->version_name,
            'build_number' => $release->build_number,
            'upload_url' => $url,
            'expires_in_minutes' => 15,
            'command' => 'curl --fail-with-body -T build/app/outputs/flutter-apk/app-release.apk "'.$url.'"',
            'next' => 'Envoie l\'APK avec command, vérifie la réponse (has_file à true, sha256), puis publish-app-release quand le développeur valide.',
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'version_name' => $schema->string()->description('Version lisible, partie avant le « + » dans pubspec.yaml (ex. 1.4.0).')->required(),
            'build_number' => $schema->integer()->description('Numéro de build, partie après le « + » dans pubspec.yaml (versionCode Android).')->required(),
            'notes' => $schema->string()->description('Notes de version en français, courtes (ce qui change pour l\'utilisateur).'),
            'sha256' => $schema->string()->description('SHA-256 de l\'APK, pour que le serveur vérifie le fichier reçu.'),
        ];
    }
}
