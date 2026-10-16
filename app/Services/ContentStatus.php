<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\CommentStatus;
use App\Enums\CommercialDocumentStatus;
use App\Enums\ContactStatus;
use App\Enums\EngagementStatus;
use App\Enums\JobApplicationStatus;
use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Enums\RevisionStatus;
use App\Enums\TestimonialStatus;
use App\Models\Appointment;
use App\Models\Certification;
use App\Models\CommercialDocument;
use App\Models\Contact;
use App\Models\ContentRevision;
use App\Models\Distinction;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Engagement;
use App\Models\Experience;
use App\Models\JobApplication;
use App\Models\JobProfile;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Project;
use App\Models\Service;
use App\Models\Skill;
use App\Models\Testimonial;
use App\Models\UsesItem;
use App\Services\Ai\ContentRevisions;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Change le seul statut d'une ligne (liste ou fiche de l'administration, API), avec les règles
 * des formulaires : on ne publie pas un contenu dont l'anglais obligatoire manque, un article
 * publié sans date paraît tout de suite, et les statuts à effets (rendez-vous, documents
 * commerciaux, révisions) passent par leur service, emails et numérotation compris.
 */
class ContentStatus
{
    public function __construct(
        private readonly BookingCalendar $calendar,
        private readonly CommercialDocuments $documents,
        private readonly ContentRevisions $revisions,
    ) {}

    /**
     * Modèle, statuts possibles et champs traduits à remplir en anglais pour publier.
     *
     * @var array<string, array{model: class-string<Model>, enum: class-string<BackedEnum>, english: list<string>}>
     */
    private const array TYPES = [
        'domain' => ['model' => Domain::class, 'enum' => PublicationStatus::class, 'english' => []],
        'job_profile' => ['model' => JobProfile::class, 'enum' => PublicationStatus::class, 'english' => []],
        'contact' => ['model' => Contact::class, 'enum' => ContactStatus::class, 'english' => []],
        'engagement' => ['model' => Engagement::class, 'enum' => EngagementStatus::class, 'english' => []],
        'job_application' => ['model' => JobApplication::class, 'enum' => JobApplicationStatus::class, 'english' => []],
        'comment' => ['model' => PostComment::class, 'enum' => CommentStatus::class, 'english' => []],
        'testimonial' => ['model' => Testimonial::class, 'enum' => TestimonialStatus::class, 'english' => []],
        'appointment' => ['model' => Appointment::class, 'enum' => AppointmentStatus::class, 'english' => []],
        'commercial_document' => ['model' => CommercialDocument::class, 'enum' => CommercialDocumentStatus::class, 'english' => []],
        'revision' => ['model' => ContentRevision::class, 'enum' => RevisionStatus::class, 'english' => []],
        'project' => ['model' => Project::class, 'enum' => ProjectStatus::class, 'english' => ['title', 'context', 'realization', 'result']],
        'post' => ['model' => Post::class, 'enum' => PublicationStatus::class, 'english' => []],
        'experience' => ['model' => Experience::class, 'enum' => PublicationStatus::class, 'english' => ['role']],
        'education' => ['model' => Education::class, 'enum' => PublicationStatus::class, 'english' => ['degree', 'field']],
        'certification' => ['model' => Certification::class, 'enum' => PublicationStatus::class, 'english' => ['name']],
        'distinction' => ['model' => Distinction::class, 'enum' => PublicationStatus::class, 'english' => ['title']],
        'uses_item' => ['model' => UsesItem::class, 'enum' => PublicationStatus::class, 'english' => []],
        'skill' => ['model' => Skill::class, 'enum' => PublicationStatus::class, 'english' => ['name']],
        'service' => ['model' => Service::class, 'enum' => PublicationStatus::class, 'english' => ['title']],
    ];

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys(self::TYPES);
    }

    public function find(string $type, int $id): ?Model
    {
        return isset(self::TYPES[$type]) ? self::TYPES[$type]['model']::query()->find($id) : null;
    }

    /**
     * @throws ValidationException
     */
    public function change(string $type, Model $content, string $status): void
    {
        $definition = self::TYPES[$type];
        $newStatus = $definition['enum']::tryFrom($status);

        if ($newStatus === null) {
            throw ValidationException::withMessages(['status' => __('Statut inconnu.')]);
        }

        if ($newStatus === $content->status) {
            return;
        }

        if (! in_array($newStatus, $this->allowedTransitions($content), true)) {
            throw ValidationException::withMessages(['status' => __('Ce changement de statut n\'est pas possible.')]);
        }

        if ($this->changeThroughService($content, $newStatus)) {
            return;
        }

        $publishing = $newStatus->value === 'published';
        $missing = $publishing ? $this->missingEnglish($definition['english'], $content) : [];

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'status' => __('Complétez l\'anglais avant de publier : :fields.', ['fields' => implode(', ', $missing)]),
            ]);
        }

        $content->status = $newStatus;

        if ($content instanceof Post && $publishing && $content->published_at === null) {
            $content->published_at = now();
        }

        if ($content instanceof Testimonial && $newStatus !== TestimonialStatus::Approved) {
            $content->is_featured = false;
        }

        $content->save();
    }

    /**
     * Les statuts qu'on peut donner à la ligne depuis son statut actuel : un rendez-vous refusé,
     * une révision traitée ou un document payé ne reviennent pas en arrière.
     *
     * @return list<BackedEnum>
     */
    public function allowedTransitions(Model $content): array
    {
        return match (true) {
            $content instanceof Appointment => match ($content->status) {
                AppointmentStatus::Pending => [AppointmentStatus::Confirmed, AppointmentStatus::Declined],
                AppointmentStatus::Confirmed => [AppointmentStatus::Declined],
                default => [],
            },
            $content instanceof CommercialDocument => match ($content->status) {
                CommercialDocumentStatus::Draft => [CommercialDocumentStatus::Issued],
                CommercialDocumentStatus::Paid, CommercialDocumentStatus::Cancelled => [],
                default => array_values(array_filter(
                    [CommercialDocumentStatus::Accepted, CommercialDocumentStatus::Declined, CommercialDocumentStatus::Paid, CommercialDocumentStatus::Cancelled],
                    fn (CommercialDocumentStatus $status): bool => $status !== $content->status,
                )),
            },
            $content instanceof ContentRevision => $content->status === RevisionStatus::Pending
                ? [RevisionStatus::Applied, RevisionStatus::Rejected]
                : [],
            default => $content->status::cases(),
        };
    }

    /** Les statuts à effets passent par le service métier ; renvoie false pour un simple champ. */
    private function changeThroughService(Model $content, BackedEnum $status): bool
    {
        match (true) {
            $content instanceof Appointment => $status === AppointmentStatus::Confirmed
                ? $this->calendar->confirm($content)
                : $this->calendar->decline($content),
            $content instanceof CommercialDocument => match ($status) {
                CommercialDocumentStatus::Issued => $this->documents->issue($content),
                CommercialDocumentStatus::Accepted => $this->documents->accept($content),
                CommercialDocumentStatus::Declined => $this->documents->decline($content),
                CommercialDocumentStatus::Paid => $this->documents->markPaid($content),
                default => $this->documents->cancel($content),
            },
            $content instanceof ContentRevision => $status === RevisionStatus::Applied
                ? $this->revisions->apply($content)
                : $this->revisions->reject($content),
            default => null,
        };

        return $content instanceof Appointment || $content instanceof CommercialDocument || $content instanceof ContentRevision;
    }

    /**
     * Les champs anglais obligatoires encore vides, réalisations d'une expérience et choix
     * techniques ou chiffres clés d'un projet compris.
     *
     * @param  list<string>  $fields
     * @return list<string>
     */
    private function missingEnglish(array $fields, Model $content): array
    {
        $missing = array_values(array_filter($fields, fn (string $field): bool => blank($content->getTranslation($field, 'en', false))));

        if ($content instanceof Project) {
            $untranslatedDecision = collect($content->decisions ?? [])->contains(fn (array $decision): bool => blank($decision['choice']['en'] ?? null) || blank($decision['reason']['en'] ?? null));
            $untranslatedFigure = collect($content->key_figures ?? [])->contains(fn (array $figure): bool => blank($figure['label']['en'] ?? null));

            if ($untranslatedDecision) {
                $missing[] = 'decisions';
            }

            if ($untranslatedFigure) {
                $missing[] = 'key_figures';
            }
        }

        if ($content instanceof Experience && $content->highlights->contains(fn ($highlight): bool => blank($highlight->getTranslation('text', 'en', false)))) {
            $missing[] = 'highlights';
        }

        return $missing;
    }
}
