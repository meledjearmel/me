<?php

namespace App\Http\Controllers;

use App\Enums\TestimonialStatus;
use App\Http\Requests\TestimonialSubmissionRequest;
use App\Jobs\SendPushNotification;
use App\Models\ReviewInvitation;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TestimonialSubmissionController extends Controller
{
    /**
     * Un avis déposé depuis le site, avec ou sans vidéo : il reste « en attente » tant que je ne l'ai
     * pas relu et approuvé dans l'admin, et n'apparaît pas avant. Déposé depuis un lien
     * d'invitation, il prend les rattachements choisis dans l'admin et compte une utilisation
     * du lien (un lien personnel ne resservira plus).
     */
    public function store(TestimonialSubmissionRequest $request): RedirectResponse
    {
        $invitation = $request->filled('invitation')
            ? ReviewInvitation::query()->where('token', $request->validated('invitation'))->first()
            : null;

        $testimonial = Testimonial::query()->create([
            'author_name' => $request->validated('author_name'),
            'author_email' => $request->validated('author_email'),
            'author_role' => $request->validated('author_role'),
            'review_invitation_id' => $invitation?->id,
            'content' => [app()->getLocale() => $request->validated('content')],
            'status' => TestimonialStatus::Pending,
            'submitted_at' => now(),
        ]);

        $testimonial->syncSubjects($invitation ? $invitation->subjectIds() : [
            'project_ids' => array_filter([$request->validated('project_id')]),
            'experience_ids' => array_filter([$request->validated('experience_id')]),
            'education_ids' => array_filter([$request->validated('education_id')]),
        ]);

        $invitation?->recordUse();

        if ($request->hasFile('video')) {
            $testimonial->attachVideo($request->file('video'));
        }

        SendPushNotification::dispatch(
            $request->hasFile('video') ? 'Nouvel avis vidéo' : 'Nouvel avis',
            $testimonial->author_name,
            ['type' => 'testimonial', 'id' => (string) $testimonial->id],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Merci pour votre avis !')]);

        return back();
    }
}
