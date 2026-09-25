<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Domain;
use App\Models\Education;
use App\Models\Experience;
use App\Models\JobProfile;
use App\Models\ProfessionalReference;
use App\Models\Profile;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Génère le CV en PDF pour un profil visé (full-stack, lead technique, chef de
 * projet, chargé IT...) à partir des données du site : le CV reste toujours à
 * jour, il n'y a pas de fichier à maintenir à côté.
 */
class CvGenerator
{
    /** Nombre de projets mis en avant dans le CV. */
    private const PROJECT_LIMIT = 5;

    /**
     * Libellés fixes du document, par langue.
     *
     * @var array<string, array<string, string>>
     */
    private const LABELS = [
        'fr' => [
            'profile' => 'Profil',
            'experience' => 'Expérience professionnelle',
            'skills' => 'Compétences',
            'projects' => 'Projets',
            'references' => 'Références',
            'education' => 'Formation',
            'languages' => 'Langues',
            'present' => "aujourd'hui",
            'french' => 'Français : courant',
            'english' => "Anglais : niveau technique avancé à l'écrit, débutant à l'oral",
        ],
        'en' => [
            'profile' => 'Profile',
            'experience' => 'Professional experience',
            'skills' => 'Skills',
            'projects' => 'Projects',
            'references' => 'References',
            'education' => 'Education',
            'languages' => 'Languages',
            'present' => 'present',
            'french' => 'French: fluent',
            'english' => 'English: advanced technical level in writing, beginner in speech',
        ],
    ];

    /** Contenu du CV, prêt pour la vue. @return array<string, mixed> */
    public function data(JobProfile $jobProfile, string $locale): array
    {
        $locale = array_key_exists($locale, self::LABELS) ? $locale : 'fr';
        $profile = Profile::query()->firstOrFail();
        $labels = self::LABELS[$locale];
        $month = fn ($date): string => $date->copy()->locale($locale)->translatedFormat('M Y');

        $summary = $jobProfile->getTranslation('cv_description', $locale, false)
            ?: $profile->getTranslation('bio_short', $locale);

        $experiences = Experience::query()
            ->published()
            ->with('highlights')
            ->orderByRaw('end_date is null desc')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Experience $experience): array => [
                'company' => $experience->company,
                'role' => $experience->getTranslation('role', $locale),
                'location' => $experience->location,
                'period' => $month($experience->start_date).' – '.($experience->end_date ? $month($experience->end_date) : $labels['present']),
                'highlights' => $experience->highlights
                    ->sortBy('sort_order')
                    ->map(fn ($highlight): string => $highlight->getTranslation('text', $locale))
                    ->values()
                    ->all(),
            ])
            ->all();

        $skills = Domain::query()
            ->published()
            ->with(['skills' => fn ($query) => $query->published()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Domain $domain): bool => $domain->skills->isNotEmpty())
            ->map(fn (Domain $domain): array => [
                'label' => $domain->getTranslation('label', $locale),
                'items' => $domain->skills->map(fn ($skill): string => $skill->getTranslation('name', $locale))->all(),
            ])
            ->values()
            ->all();

        $projects = $jobProfile->projects()
            ->where('status', ProjectStatus::Published)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(self::PROJECT_LIMIT)
            ->get();

        if ($projects->isEmpty()) {
            $projects = Project::query()
                ->where('status', ProjectStatus::Published)
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->limit(self::PROJECT_LIMIT)
                ->get();
        }

        $educations = Education::query()->published()->orderByDesc('end_date')->get();

        return [
            'locale' => $locale,
            'labels' => $labels,
            // Sur le CV : nom et prénom séparés s'ils sont renseignés, sinon le nom du site.
            'first_name' => $this->cvFirstName($profile),
            'last_name' => $this->cvLastName($profile),
            'title' => $jobProfile->getTranslation('label', $locale),
            'headline' => $profile->getTranslation('headline', $locale),
            'email' => $profile->email,
            'phone' => $profile->phone,
            'location' => $profile->location,
            'links' => array_filter([
                'GitHub' => $profile->social_links['github'] ?? null,
                'LinkedIn' => $profile->social_links['linkedin'] ?? null,
            ]),
            'photo' => $this->cvPhotoDataUri($profile),
            'summary' => $summary,
            'experiences' => $experiences,
            'skills' => $skills,
            'projects' => $projects->map(fn (Project $project): array => [
                'title' => $project->getTranslation('title', $locale),
                'result' => $project->getTranslation('result', $locale),
            ])->all(),
            'educations' => $educations->map(fn (Education $education): array => [
                'degree' => $education->getTranslation('degree', $locale),
                'institution' => $education->institution,
                'year' => $education->end_date?->format('Y'),
            ])->all(),
            'languages' => [$labels['french'], $labels['english']],
            'references' => $this->references(),
        ];
    }

    /**
     * Références à joindre au CV : uniquement celles cochées « à inclure », et
     * pour chacune seulement les champs autorisés (jamais les notes privées).
     *
     * @return list<array<string, string>>
     */
    private function references(): array
    {
        return ProfessionalReference::query()
            ->where('is_public', true)
            ->orderBy('id')
            ->get()
            ->map(function (ProfessionalReference $reference): array {
                $allowed = $reference->visible_fields ?? [];

                return collect(['name', 'role', 'company', 'relationship', 'email', 'phone'])
                    ->filter(fn (string $field): bool => in_array($field, $allowed, true) && filled($reference->{$field}))
                    ->mapWithKeys(fn (string $field): array => [$field => (string) $reference->{$field}])
                    ->all();
            })
            ->filter()
            ->values()
            ->all();
    }

    /** Le PDF du CV, sous forme de contenu binaire. */
    public function pdf(JobProfile $jobProfile, string $locale): string
    {
        return Pdf::loadView('cv.document', ['cv' => $this->data($jobProfile, $locale)])
            ->setPaper('a4')
            ->output();
    }

    /** Nom de fichier du CV joint à l'email. */
    public function filename(JobProfile $jobProfile): string
    {
        $profile = Profile::query()->firstOrFail();

        $fullName = trim($this->cvLastName($profile).' '.$this->cvFirstName($profile));

        return 'CV-'.str($fullName)->slug()->title()->toString().'-'.str($jobProfile->key)->slug().'.pdf';
    }

    private function cvLastName(Profile $profile): string
    {
        return (string) $profile->cv_last_name;
    }

    /** Sans identité dédiée au CV, le nom du site tient lieu de prénom (et le nom reste vide). */
    private function cvFirstName(Profile $profile): string
    {
        if (filled($profile->cv_last_name) || filled($profile->cv_first_name)) {
            return (string) $profile->cv_first_name;
        }

        return $profile->name;
    }

    /**
     * La photo du CV, distincte de la photo du site : sans photo dédiée, le CV n'en
     * affiche pas. dompdf lit les images en data URI, sans accès réseau.
     */
    private function cvPhotoDataUri(Profile $profile): ?string
    {
        $media = $profile->getFirstMedia('cv_photo');

        if ($media === null || ! is_file($media->getPath())) {
            return null;
        }

        return 'data:'.$media->mime_type.';base64,'.base64_encode((string) file_get_contents($media->getPath()));
    }
}
