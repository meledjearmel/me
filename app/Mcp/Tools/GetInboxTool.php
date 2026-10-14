<?php

namespace App\Mcp\Tools;

use App\Enums\AppointmentStatus;
use App\Enums\CommentStatus;
use App\Enums\ContactStatus;
use App\Enums\EngagementStatus;
use App\Enums\TestimonialStatus;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Engagement;
use App\Models\PostComment;
use App\Models\Testimonial;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-inbox')]
#[Description('Ce qui attend une réponse du développeur : messages de contact non lus, demandes de collaboration nouvelles (freelance, recrutement, estimations), rendez-vous à confirmer, avis et commentaires à modérer, les plus récents d\'abord, avec leur identifiant (pour decide-appointment, moderate-testimonial, moderate-comment, mark-handled) et le lien de leur fiche. Les adresses email et numéros de téléphone ne sont pas donnés.')]
#[IsReadOnly]
class GetInboxTool extends Tool
{
    private const int LIMIT = 15;

    public function handle(Request $request): Response
    {
        $excerpt = fn (?string $text): ?string => $text === null ? null : Str::limit(trim($text), 500);

        return Response::json([
            'contacts' => Contact::query()->where('status', ContactStatus::New)->latest()->limit(self::LIMIT)->get()
                ->map(fn (Contact $contact): array => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'subject' => $contact->subject,
                    'message' => $excerpt($contact->message),
                    'received_at' => $contact->created_at?->toIso8601String(),
                    'admin_url' => route('admin.contacts.show', $contact),
                ])->all(),
            'engagements' => Engagement::query()->where('status', EngagementStatus::New)->latest()->limit(self::LIMIT)->get()
                ->map(fn (Engagement $engagement): array => [
                    'id' => $engagement->id,
                    'type' => $engagement->type->value,
                    'name' => $engagement->name,
                    'company' => $engagement->company,
                    'subject' => $engagement->subject,
                    'estimate' => $engagement->estimate_label,
                    'budget' => $engagement->budget_label,
                    'message' => $excerpt($engagement->message),
                    'received_at' => $engagement->created_at?->toIso8601String(),
                    'admin_url' => route('admin.engagements.show', $engagement),
                ])->all(),
            'appointments' => Appointment::query()->with('appointmentType')->where('status', AppointmentStatus::Pending)->orderBy('starts_at')->limit(self::LIMIT)->get()
                ->map(fn (Appointment $appointment): array => [
                    'id' => $appointment->id,
                    'type' => $appointment->appointmentType?->getTranslation('name', 'fr'),
                    'name' => $appointment->name,
                    'company' => $appointment->company,
                    'starts_at' => $appointment->starts_at?->toIso8601String(),
                    'location' => $appointment->location?->value,
                    'message' => $excerpt($appointment->message),
                    'admin_url' => route('admin.appointments.show', $appointment),
                ])->all(),
            'testimonials' => Testimonial::query()->with('project')->where('status', TestimonialStatus::Pending)->latest('submitted_at')->limit(self::LIMIT)->get()
                ->map(fn (Testimonial $testimonial): array => [
                    'id' => $testimonial->id,
                    'author_name' => $testimonial->author_name,
                    'author_role' => $testimonial->author_role,
                    'content' => $excerpt($testimonial->getTranslation('content', 'fr', false) ?: $testimonial->getTranslation('content', 'en', false)),
                    'project' => $testimonial->project?->getTranslation('title', 'fr'),
                    'admin_url' => route('admin.testimonials.show', $testimonial),
                ])->all(),
            'comments' => PostComment::query()->with('post')->where('status', CommentStatus::Pending)->latest()->limit(self::LIMIT)->get()
                ->map(fn (PostComment $comment): array => [
                    'id' => $comment->id,
                    'author_name' => $comment->author_name,
                    'body' => $excerpt($comment->body),
                    'post' => $comment->post?->getTranslation('title', 'fr'),
                    'received_at' => $comment->created_at?->toIso8601String(),
                ])->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
