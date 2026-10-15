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

#[Name('prepare-signing-key-backup')]
#[Description('Prépare la sauvegarde de la clé de signature (keystore) de l\'application mobile : enregistre le fichier annoncé et renvoie une adresse d\'envoi signée, valable 15 minutes, avec la commande curl prête à lancer (corps brut, PUT). Le fichier doit être chiffré au préalable sur le poste du développeur (age, zip AES-256…), avec une phrase secrète qu\'il saisit lui-même : n\'envoie jamais de fichier en clair, et ne transmets jamais au serveur la phrase secrète ni le mot de passe du keystore, dans aucun paramètre. Une fois reçu et vérifié (SHA-256), le fichier devient la sauvegarde courante ; l\'ancienne est conservée et marquée remplacée.')]
class PrepareSigningKeyBackupTool extends Tool
{
    public function handle(Request $request): Response
    {
        $request->validate([
            'filename' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z0-9._-]+$/'],
            'sha256' => ['required', 'string', 'regex:/^[A-Fa-f0-9]{64}$/'],
            'key_fingerprint' => ['required', 'string', 'regex:/^([A-Fa-f0-9]{2}:?){31}[A-Fa-f0-9]{2}$/'],
            'encryption' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $backup = SigningKeyBackup::query()->create([
            'filename' => $request->get('filename'),
            'sha256' => strtolower($request->get('sha256')),
            'key_fingerprint' => SigningKeyBackup::normalizeFingerprint($request->get('key_fingerprint')),
            'encryption' => $request->get('encryption'),
            'notes' => $request->get('notes'),
        ]);

        $url = $backup->uploadUrl();

        return Response::json([
            'id' => $backup->id,
            'filename' => $backup->filename,
            'key_fingerprint' => $backup->key_fingerprint,
            'upload_url' => $url,
            'expires_in_minutes' => 15,
            'command' => 'curl --fail-with-body -T '.$backup->filename.' "'.$url.'"',
            'next' => 'Envoie le fichier chiffré avec command, depuis son dossier, puis vérifie la réponse (has_file à true, sha256). Ne journalise pas l\'adresse.',
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filename' => $schema->string()->description('Nom du fichier chiffré (ex. me-admin-release.age).')->required(),
            'sha256' => $schema->string()->description('SHA-256 du fichier chiffré, pour que le serveur vérifie ce qu\'il reçoit.')->required(),
            'key_fingerprint' => $schema->string()->description('Empreinte SHA-256 du certificat de signature (keytool -list -v), format AB:CD:…')->required(),
            'encryption' => $schema->string()->description('Méthode de chiffrement utilisée, ex. « age » ou « zip-aes256 ».')->required(),
            'notes' => $schema->string()->description('Notes facultatives (alias de la clé, outil de déchiffrement…), sans aucun secret.'),
        ];
    }
}
