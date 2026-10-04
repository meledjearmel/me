<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GitHubSelectionRequest;
use App\Models\SiteSetting;
use App\Services\GitHubActivity;
use Illuminate\Http\JsonResponse;

/**
 * @tags GitHub
 */
class GitHubController extends Controller
{
    public function __construct(private GitHubActivity $github) {}

    /**
     * Dépôts GitHub de la page À propos
     *
     * `available` : tous les dépôts de la dernière synchronisation (les miens, privés compris,
     * et ceux auxquels j'ai contribué quand un jeton GitHub est configuré). `selected` : ceux
     * choisis, dans l'ordre (vide : choix automatique parmi mes dépôts publics). Un dépôt
     * privé choisi s'affiche sans lien.
     */
    public function show(): JsonResponse
    {
        return response()->json($this->payload());
    }

    /**
     * Choisir les dépôts GitHub à présenter
     *
     * `repositories` : noms complets (« propriétaire/nom ») pris dans `available`, dans
     * l'ordre d'affichage ; une liste vide revient au choix automatique.
     */
    public function update(GitHubSelectionRequest $request): JsonResponse
    {
        SiteSetting::current()->update(['github_repositories' => array_values($request->validated('repositories'))]);

        return response()->json($this->payload());
    }

    /**
     * Synchroniser maintenant
     *
     * Répond 503 si GitHub n'a pas répondu (la dernière version est conservée).
     */
    public function sync(): JsonResponse
    {
        abort_unless($this->github->sync(), 503, __('GitHub n’a pas répondu : la dernière version est conservée.'));

        return response()->json($this->payload());
    }

    /**
     * @return array{available: list<array{full_name: string, name: string, description: string|null, language: string|null, stars: int, url: string, pushed_at: string|null, private: bool, archived: bool, contribution: bool}>, selected: list<string>, synced_at: string|null, has_token: bool}
     */
    private function payload(): array
    {
        $cached = $this->github->cached();

        return [
            'available' => $cached['available'] ?? [],
            'selected' => SiteSetting::current()->github_repositories ?? [],
            'synced_at' => $cached['synced_at'] ?? null,
            /** Un jeton GitHub est configuré (contributions et dépôts privés disponibles). */
            'has_token' => $this->github->hasToken(),
        ];
    }
}
