<?php

namespace App\Mcp\Tools;

use App\Models\SigningKeyBackup;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-signing-key-backup')]
#[Description('Renvoie la sauvegarde courante de la clé de signature de l\'application mobile (nom du fichier, taille, SHA-256, empreinte du certificat key_fingerprint, méthode de chiffrement, date) avec une adresse de téléchargement signée, valable 15 minutes, ou null s\'il n\'y en a pas. Le fichier est chiffré côté client : déchiffre-le sur le poste du développeur, avec la phrase secrète qu\'il saisit lui-même. Ne transmets jamais au serveur la phrase secrète, le mot de passe du keystore ni la clé en clair. Compare key_fingerprint à l\'empreinte de la clé locale (keytool -list -v) pour vérifier qu\'il s\'agit de la même clé.')]
#[IsReadOnly]
class GetSigningKeyBackupTool extends Tool
{
    public function handle(Request $request): Response
    {
        $backup = SigningKeyBackup::current();

        if ($backup === null) {
            return Response::json(['backup' => null]);
        }

        return Response::json(['backup' => [
            'filename' => $backup->filename,
            'size' => $backup->size,
            'sha256' => $backup->sha256,
            'key_fingerprint' => $backup->key_fingerprint,
            'encryption' => $backup->encryption,
            'notes' => $backup->notes,
            'created_at' => $backup->created_at?->toIso8601String(),
            'has_file' => $backup->has_file,
            'download_url' => $backup->downloadUrl(),
            'expires_in_minutes' => 15,
        ]]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
