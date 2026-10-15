<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SigningKeyBackup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sauvegarde chiffrée de la clé de signature de l'app mobile. Le chiffrement se fait sur le
 * poste du développeur : le serveur ne reçoit qu'un blob opaque, jamais la phrase secrète.
 *
 * @tags Application mobile
 */
class SigningKeyBackupController extends Controller
{
    /**
     * Envoyer la sauvegarde chiffrée de la clé de signature
     *
     * Adresse signée donnée par l'outil MCP prepare-signing-key-backup, valable 15 minutes : le
     * corps de la requête PUT est le blob déjà chiffré (curl -T me-admin-release.age "<adresse>").
     * Son SHA-256 doit correspondre à celui annoncé, sinon le fichier est refusé (422).
     *
     * @unauthenticated
     *
     * @response array{data: array{id: int, size: int|null, sha256: string, key_fingerprint: string, has_file: bool, created_at: string|null}}
     */
    public function upload(Request $request, SigningKeyBackup $backup): JsonResponse
    {
        $temporaryPath = (string) tempnam(sys_get_temp_dir(), 'skb');

        try {
            $input = $request->getContent(true);
            $output = fopen($temporaryPath, 'wb');
            $copied = stream_copy_to_stream($input, $output, SigningKeyBackup::MAX_BYTES + 1);
            fclose($output);

            if ($copied === false || $copied > SigningKeyBackup::MAX_BYTES) {
                return response()->json(['message' => 'Fichier refusé : une sauvegarde chiffrée de 1 Mo au plus est attendue.'], 413);
            }

            $backup->attachBlob($temporaryPath);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } finally {
            @unlink($temporaryPath);
        }

        return response()->json(['data' => [
            'id' => $backup->id,
            'size' => $backup->size,
            'sha256' => $backup->sha256,
            'key_fingerprint' => $backup->key_fingerprint,
            'has_file' => $backup->has_file,
            'created_at' => $backup->created_at?->toIso8601String(),
        ]]);
    }

    /**
     * Télécharger la sauvegarde chiffrée de la clé de signature
     *
     * Adresse signée donnée par l'outil MCP get-signing-key-backup, valable 15 minutes. Renvoie
     * le blob chiffré tel qu'il a été envoyé, à déchiffrer sur le poste du développeur.
     *
     * @unauthenticated
     */
    public function download(SigningKeyBackup $backup): StreamedResponse
    {
        abort_unless($backup->has_file, 404);

        return Storage::disk(SigningKeyBackup::DISK)->download($backup->file_path, $backup->filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
