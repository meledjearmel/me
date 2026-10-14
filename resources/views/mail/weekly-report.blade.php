@php
    $audience = $report['audience'];
    $requests = $report['requests'];
    $estimator = $report['estimator'] ?? [];
    $outcomes = $report['outcomes'] ?? [];
    $applications = $report['applications'];
    $blog = $report['blog'];
    $change = $audience['change'];
    $todoLabels = [
        'contacts' => 'messages de contact',
        'engagements' => 'demandes de collaboration',
        'testimonials' => 'avis à valider',
        'appointments' => 'rendez-vous à confirmer',
        'comments' => 'commentaires à modérer',
        'project_drafts' => 'projets en brouillon',
        'post_drafts' => 'articles en brouillon',
        'content_drafts' => 'autres contenus en brouillon',
        'revisions' => 'modifications proposées par l’IA',
    ];
@endphp
<x-mail::message>
# Ta semaine sur le portfolio

@if ($report['highlights'])
**À retenir**

@foreach ($report['highlights'] as $highlight)
- {{ $highlight }}
@endforeach
@endif

## Audience

**{{ $audience['visits'] }} visites** par **{{ $audience['visitors'] }} visiteurs**{{ $change !== null ? ', '.($change >= 0 ? '+' : '').$change.' % par rapport à la semaine d’avant ('.$audience['previous_visits'].' visites)' : '' }}.

@if ($audience['top_content'] !== [])
<x-mail::table>
| Le plus lu | Visites |
|:--|--:|
@foreach ($audience['top_content'] as $content)
| [{{ $content['title'] }}]({{ url($content['url']) }}) | {{ $content['visits'] }} |
@endforeach
</x-mail::table>
@endif

@if ($audience['sources'] !== [])
Provenance : {{ collect($audience['sources'])->map(fn ($source) => $source['label'].' ('.$source['count'].')')->implode(', ') }}.
@endif

## Demandes

@if ($requests['total'] === 0)
Aucune nouvelle demande cette semaine.
@else
{{ $requests['contacts'] }} message{{ $requests['contacts'] > 1 ? 's' : '' }} · {{ $requests['freelance'] }} projet{{ $requests['freelance'] > 1 ? 's' : '' }} freelance · {{ $requests['hiring'] }} recrutement{{ $requests['hiring'] > 1 ? 's' : '' }} · {{ $requests['appointments'] }} rendez-vous demandé{{ $requests['appointments'] > 1 ? 's' : '' }}
@endif
@if ($requests['upcoming_appointments'] > 0)

{{ $requests['upcoming_appointments'] }} rendez-vous confirmé{{ $requests['upcoming_appointments'] > 1 ? 's' : '' }} dans les 7 prochains jours.
@endif

@if (($estimator['page_visitors'] ?? 0) > 0 || ($estimator['requests'] ?? 0) > 0)

Estimateur : {{ $estimator['page_visitors'] }} visiteur{{ $estimator['page_visitors'] > 1 ? 's' : '' }} sur sa page, {{ $estimator['requests'] }} demande{{ $estimator['requests'] > 1 ? 's' : '' }} envoyée{{ $estimator['requests'] > 1 ? 's' : '' }} ({{ str_replace('.', ',', (string) $estimator['conversion_rate']) }} %){{ collect($estimator['by_currency'])->map(fn (array $row): string => ', '.number_format($row['price_min'], 0, ',', ' ').' – '.number_format($row['price_max'], 0, ',', ' ').' '.$row['currency'].' estimés')->implode('') }}{{ $estimator['ai_gap']['hours_gap_percent'] !== null ? ', l’IA chiffre en moyenne '.($estimator['ai_gap']['hours_gap_percent'] > 0 ? '+' : '').str_replace('.', ',', (string) $estimator['ai_gap']['hours_gap_percent']).' % d’heures' : '' }}.
@endif

@if (($outcomes['won'] ?? 0) > 0 || ($outcomes['lost'] ?? 0) > 0)

Issues : {{ $outcomes['won'] }} demande{{ $outcomes['won'] > 1 ? 's' : '' }} signée{{ $outcomes['won'] > 1 ? 's' : '' }}{{ collect($outcomes['by_currency'])->map(fn (array $row): string => ', '.number_format($row['amount'], 0, ',', ' ').' '.$row['currency'])->implode('') }}, {{ $outcomes['lost'] }} perdue{{ $outcomes['lost'] > 1 ? 's' : '' }}.
@endif

@if ($requests['estimates'] !== [])
<x-mail::table>
| Estimation | Affichée | Avis de l’IA |
|:--|:--|:--|
@foreach ($requests['estimates'] as $estimate)
| [{{ $estimate['subject'] }}]({{ route('admin.engagements.show', $estimate['id']) }}) | {{ $estimate['estimate'] }} | {{ $estimate['ai_hours'] ? $estimate['ai_hours'].($estimate['ai_billing'] === 'hourly' ? ', à l’heure' : ', forfait') : '—' }} |
@endforeach
</x-mail::table>
@endif

@if ($applications['viewed'] !== [] || $applications['created'] > 0)
## Candidatures

@foreach ($applications['viewed'] as $application)
- **[{{ $application['company'] }}]({{ route('admin.job-applications.show', $application['id']) }})** ({{ $application['position'] }}) : {{ $application['views'] }} consultation{{ $application['views'] > 1 ? 's' : '' }}{{ $application['cv_downloads'] > 0 ? ', CV téléchargé '.$application['cv_downloads'].' fois' : '' }}{{ $application['first_time'] ? ', ouverte pour la première fois' : '' }}
@endforeach
@if ($applications['created'] > 0)

{{ $applications['created'] }} page{{ $applications['created'] > 1 ? 's' : '' }} de candidature créée{{ $applications['created'] > 1 ? 's' : '' }} cette semaine.
@endif
@endif

## CV et blog

{{ $report['cv_downloads'] }} CV téléchargé{{ $report['cv_downloads'] > 1 ? 's' : '' }} · {{ $blog['published'] }} article{{ $blog['published'] > 1 ? 's' : '' }} publié{{ $blog['published'] > 1 ? 's' : '' }} · {{ $blog['comments'] }} commentaire{{ $blog['comments'] > 1 ? 's' : '' }} · {{ $blog['reactions'] }} réaction{{ $blog['reactions'] > 1 ? 's' : '' }} · {{ $blog['new_subscribers'] }} nouvel{{ $blog['new_subscribers'] > 1 ? 's' : '' }} abonné{{ $blog['new_subscribers'] > 1 ? 's' : '' }} ({{ $blog['subscribers'] }} au total)

@if ($report['todo'] !== [])
## À traiter

@foreach ($report['todo'] as $key => $count)
- {{ $count }} {{ $todoLabels[$key] ?? $key }}
@endforeach
@endif

<x-mail::button :url="route('dashboard')">
Ouvrir le tableau de bord
</x-mail::button>

Tu reçois ce rapport chaque lundi. Pour l’arrêter : [Rapport de la semaine]({{ route('admin.weekly-report.show') }}) dans l’administration.
</x-mail::message>
