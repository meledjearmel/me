<x-mail::message>
# {{ $engagement->type->value === 'hiring' ? 'Nouvelle demande de recrutement' : 'Nouveau projet freelance' }}

**{{ $engagement->name }}**{{ $engagement->company ? ' ('.$engagement->company.')' : '' }} vous a écrit depuis le portfolio ({{ $engagement->locale === 'en' ? 'version anglaise' : 'version française' }}).

<x-mail::table>
| | |
|:--|:--|
| **Email** | {{ $engagement->email }} |
@if ($engagement->subject)
| **{{ $engagement->type->value === 'hiring' ? 'Poste' : 'Projet' }}** | {{ $engagement->subject }} |
@endif
@if ($engagement->jobProfile)
| **CV envoyé** | {{ $engagement->jobProfile->getTranslation('label', 'fr') }}{{ $engagement->cv_sent_at ? '' : ' (envoi en cours)' }} |
@endif
@if ($engagement->contract)
| **Contrat** | {{ $engagement->contract }} |
@endif
@if ($engagement->budget_label)
| **Budget** | {{ $engagement->budget_label }} |
@endif
@if ($engagement->timeline)
| **Délai** | {{ $engagement->timeline }} |
@endif
</x-mail::table>

@if ($engagement->message)
> {{ $engagement->message }}
@endif

<x-mail::button :url="route('admin.engagements.show', $engagement)">
Voir la demande
</x-mail::button>

Répondez directement à cet email : votre réponse partira à {{ $engagement->name }}.
</x-mail::message>
