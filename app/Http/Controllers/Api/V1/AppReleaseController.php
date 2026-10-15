<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @tags Application mobile
 */
class AppReleaseController extends Controller
{
    /**
     * Dernière version publiée
     *
     * À appeler au démarrage de l'app : si build_number dépasse le numéro de build installé,
     * proposer la mise à jour et télécharger l'APK depuis download_url (adresse signée, valable
     * 30 minutes, sans en-tête d'authentification). Réponse 204 si aucune version n'est publiée.
     *
     * @response array{data: array{id: int, version_name: string, build_number: int, notes: string|null, size: int|null, sha256: string|null, published_at: string|null, download_url: string}}
     */
    public function latest(): JsonResponse|Response
    {
        $release = AppRelease::latestPublished();

        if ($release === null) {
            return response()->noContent();
        }

        return response()->json(['data' => [
            'id' => $release->id,
            'version_name' => $release->version_name,
            'build_number' => $release->build_number,
            'notes' => $release->notes,
            'size' => $release->size,
            'sha256' => $release->sha256,
            'published_at' => $release->published_at?->toIso8601String(),
            'download_url' => $release->downloadUrl(),
        ]]);
    }

    /**
     * Envoyer l'APK d'un build
     *
     * Adresse signée donnée par l'outil MCP prepare-app-release, valable 15 minutes : le corps
     * de la requête PUT est l'APK brut (curl -T app-release.apk "<adresse>").
     *
     * @unauthenticated
     */
    public function upload(Request $request, AppRelease $appRelease): JsonResponse
    {
        $temporaryPath = (string) tempnam(sys_get_temp_dir(), 'apk');

        try {
            $input = $request->getContent(true);
            $output = fopen($temporaryPath, 'wb');
            $copied = stream_copy_to_stream($input, $output, AppRelease::MAX_BYTES + 1);
            fclose($output);

            if ($copied === false || $copied > AppRelease::MAX_BYTES) {
                return response()->json(['message' => 'Fichier refusé : un APK de 128 Mo au plus est attendu.'], 413);
            }

            $appRelease->attachApk($temporaryPath);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } finally {
            @unlink($temporaryPath);
        }

        return response()->json(['data' => $appRelease->summary()]);
    }

    /**
     * Télécharger l'APK d'une version publiée
     *
     * Adresse signée renvoyée par « Dernière version publiée ».
     *
     * @unauthenticated
     */
    public function download(AppRelease $appRelease): StreamedResponse
    {
        abort_unless($appRelease->isPublished() && $appRelease->hasFile(), 404);

        return Storage::disk(AppRelease::DISK)->download($appRelease->file_path, $appRelease->fileName(), [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
