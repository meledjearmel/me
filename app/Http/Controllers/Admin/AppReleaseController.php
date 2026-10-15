<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\SigningKeyBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds privés de l'application mobile : historique, envoi manuel d'un APK, publication,
 * téléchargement. Claude Code les envoie aussi par le serveur MCP (prepare-app-release).
 */
class AppReleaseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/app-releases/index', [
            'releases' => AppRelease::query()
                ->orderByDesc('build_number')
                ->get()
                ->map(fn (AppRelease $release): array => $release->summary()),
            'signingKeyBackup' => SigningKeyBackup::current()?->only(['created_at', 'key_fingerprint']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/app-releases/create', [
            'nextBuildNumber' => AppRelease::highestBuildNumber() + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'version_name' => ['required', 'string', 'max:50', 'regex:/^\d+\.\d+\.\d+([-.][0-9A-Za-z.-]+)?$/'],
            'build_number' => ['required', 'integer', 'min:'.(AppRelease::highestBuildNumber() + 1), 'max:2100000000', Rule::unique(AppRelease::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'apk' => ['required', 'file', 'max:'.(AppRelease::MAX_BYTES / 1024)],
        ]);

        $release = AppRelease::query()->create($validated);

        try {
            $release->attachApk($request->file('apk')->getRealPath());
        } catch (RuntimeException $exception) {
            $release->delete();

            throw ValidationException::withMessages(['apk' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Build envoyé. Publiez-le pour le proposer à l\'app.')]);

        return to_route('admin.app-releases.index');
    }

    public function publish(AppRelease $appRelease): RedirectResponse
    {
        $latest = AppRelease::latestPublished();

        if (! $appRelease->hasFile() || ($latest !== null && $latest->build_number > $appRelease->build_number)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Ce build ne peut pas être publié : APK manquant ou version plus récente déjà publiée.')]);

            return to_route('admin.app-releases.index');
        }

        $appRelease->forceFill(['published_at' => $appRelease->published_at ?? now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Version publiée : l\'app la proposera au prochain démarrage.')]);

        return to_route('admin.app-releases.index');
    }

    public function download(AppRelease $appRelease): StreamedResponse
    {
        abort_unless($appRelease->hasFile(), 404);

        return Storage::disk(AppRelease::DISK)->download($appRelease->file_path, $appRelease->fileName(), [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }

    public function destroy(AppRelease $appRelease): RedirectResponse
    {
        $appRelease->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Build supprimé.')]);

        return to_route('admin.app-releases.index');
    }
}
