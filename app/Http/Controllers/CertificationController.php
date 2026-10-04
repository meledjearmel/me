<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Page « Certifications » : certifications obtenues et formations courtes suivies.
 * Seules les entrées publiées s'affichent ; sans aucune, la page n'existe pas (404).
 */
class CertificationController extends Controller
{
    public function index(string $locale): Response
    {
        $certifications = Certification::query()
            ->published()
            ->with('media')
            ->orderBy('sort_order')
            ->orderByDesc('issued_on')
            ->get();

        abort_if($certifications->isEmpty(), HttpResponse::HTTP_NOT_FOUND);

        return Inertia::render('public/certifications', [
            'certifications' => $certifications->map(fn (Certification $certification): array => [
                'id' => $certification->id,
                'kind' => $certification->kind->value,
                'name' => $certification->getTranslation('name', $locale),
                'issuer' => $certification->issuer,
                'issued_on' => $certification->issued_on->toDateString(),
                'expires_on' => $certification->expires_on?->toDateString(),
                'expired' => $certification->isExpired(),
                'credential_id' => $certification->credential_id,
                'credential_url' => $certification->credential_url,
                'badge_url' => $certification->getFirstMediaUrl('badge') ?: null,
            ])->values(),
        ]);
    }
}
