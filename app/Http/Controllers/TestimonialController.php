<?php

namespace App\Http\Controllers;

use App\Enums\TestimonialStatus;
use App\Http\Resources\Public\TestimonialResource;
use App\Models\ReviewInvitation;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    /**
     * Tous les avis approuvés (l'accueil n'en montre que les trois derniers). Avec
     * ?invitation=…, le formulaire d'avis s'ouvre prérempli pour la personne invitée.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('public/testimonials', [
            'testimonials' => TestimonialResource::collection(
                Testimonial::query()
                    ->with('media')
                    ->where('status', TestimonialStatus::Approved)
                    ->latest('submitted_at')
                    ->get(),
            ),
            'reviewInvitation' => $this->invitationFor($request->query('invitation')),
        ]);
    }

    /**
     * Ce que le formulaire préremplit pour un lien d'invitation, ou `valid: false`
     * quand le lien est inconnu, déjà utilisé ou expiré.
     *
     * @return array{valid: bool, token?: string, name?: ?string, email?: ?string, subject?: ?string}|null
     */
    private function invitationFor(mixed $token): ?array
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $invitation = ReviewInvitation::query()
            ->with(['project', 'experience', 'education'])
            ->where('token', $token)
            ->first();

        if (! $invitation?->isUsable()) {
            return ['valid' => false];
        }

        return [
            'valid' => true,
            'token' => $invitation->token,
            'name' => $invitation->name,
            'email' => $invitation->email,
            'subject' => $invitation->subjectLabel(app()->getLocale()),
        ];
    }
}
