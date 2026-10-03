<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Contact;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Engagement;
use App\Models\Experience;
use App\Models\JobProfile;
use App\Models\MusicGenre;
use App\Models\ProfessionalReference;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Technology;
use App\Models\TechnologyCategory;
use App\Models\Testimonial;
use App\Models\Track;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TrashController extends Controller
{
    /** @var array<string, array{model: class-string<Model>, label: string}> */
    private const array TYPES = [
        'domains' => ['model' => Domain::class, 'label' => 'Domaine'],
        'music-genres' => ['model' => MusicGenre::class, 'label' => 'Registre musical'],
        'tracks' => ['model' => Track::class, 'label' => 'Piste audio'],
        'technology-categories' => ['model' => TechnologyCategory::class, 'label' => 'Catégorie de technologie'],
        'technologies' => ['model' => Technology::class, 'label' => 'Technologie'],
        'job-profiles' => ['model' => JobProfile::class, 'label' => 'Profil métier'],
        'skills' => ['model' => Skill::class, 'label' => 'Compétence'],
        'educations' => ['model' => Education::class, 'label' => 'Formation'],
        'experiences' => ['model' => Experience::class, 'label' => 'Expérience'],
        'projects' => ['model' => Project::class, 'label' => 'Projet'],
        'professional-references' => ['model' => ProfessionalReference::class, 'label' => 'Référence professionnelle'],
        'testimonials' => ['model' => Testimonial::class, 'label' => 'Avis'],
        'contacts' => ['model' => Contact::class, 'label' => 'Message de contact'],
        'engagements' => ['model' => Engagement::class, 'label' => 'Demande de collaboration'],
        'appointments' => ['model' => Appointment::class, 'label' => 'Rendez-vous'],
        'appointment-types' => ['model' => AppointmentType::class, 'label' => 'Type de rendez-vous'],
    ];

    public function index(Request $request): Response
    {
        $items = collect(self::TYPES)
            ->flatMap(fn (array $config, string $type) => $config['model']::onlyTrashed()->get()
                ->map(fn (Model $model): array => [
                    'id' => $model->id,
                    'type' => $type,
                    'label' => $config['label'],
                    'title' => $this->title($type, $model),
                    'deleted_at' => $model->deleted_at,
                ]));

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $items = $items->filter(fn (array $item): bool => str_contains(Str::lower($item['title']), Str::lower($search)));
        }

        $type = $request->query('type');
        if (is_string($type) && $type !== '') {
            $items = $items->where('type', $type);
        }

        $items = $items->sortByDesc('deleted_at')->values();

        $perPage = in_array((int) $request->query('per_page'), [10, 25, 50], true) ? (int) $request->query('per_page') : 10;
        $page = max(1, (int) $request->query('page', 1));

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('admin/trash/index', [
            'items' => $paginator,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'type' => is_string($type) ? $type : '',
            ],
            'types' => collect(self::TYPES)
                ->map(fn (array $config, string $type): array => ['value' => $type, 'label' => $config['label']])
                ->values(),
        ]);
    }

    public function restore(string $type, int $id): RedirectResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        self::TYPES[$type]['model']::onlyTrashed()->findOrFail($id)->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément restauré.')]);

        return to_route('admin.trash.index');
    }

    public function forceDelete(string $type, int $id): RedirectResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        self::TYPES[$type]['model']::onlyTrashed()->findOrFail($id)->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Élément supprimé définitivement.')]);

        return to_route('admin.trash.index');
    }

    private function title(string $type, Model $model): string
    {
        return match ($type) {
            'domains', 'music-genres', 'job-profiles', 'technology-categories' => (string) $model->label,
            'technologies', 'skills', 'professional-references' => (string) $model->name,
            'tracks' => trim("{$model->title} — {$model->artist}"),
            'educations' => trim("{$model->degree} · {$model->institution}"),
            'experiences' => trim("{$model->role} · {$model->company}"),
            'projects' => (string) $model->title,
            'testimonials' => (string) $model->author_name,
            'contacts', 'engagements' => trim("{$model->name} — {$model->subject}"),
            'appointments' => trim("{$model->name} — {$model->starts_at?->format('d/m/Y H:i')}"),
            'appointment-types' => (string) $model->name,
            default => (string) $model->id,
        };
    }
}
