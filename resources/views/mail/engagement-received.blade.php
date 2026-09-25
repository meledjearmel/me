<x-mail::message>
# {{ $engagement->type->value === 'hiring' ? 'Nouvelle demande de recrutement' : 'Nouveau projet freelance' }}

**{{ $engagement->name }}** ({{ $engagement->email }})@if ($engagement->company) — {{ $engagement->company }}@endif

@if ($engagement->subject)
**{{ $engagement->type->value === 'hiring' ? 'Poste' : 'Projet' }} :** {{ $engagement->subject }}
@endif

@if ($engagement->jobProfile)
**Profil du CV envoyé :** {{ $engagement->jobProfile->getTranslation('label', $engagement->locale) }}
@endif

@if ($engagement->contract)
**Contrat :** {{ $engagement->contract }}
@endif

@if ($engagement->budget_label)
**Budget :** {{ $engagement->budget_label }}
@endif

@if ($engagement->timeline)
**Délai :** {{ $engagement->timeline }}
@endif

@if ($engagement->message)
> {{ $engagement->message }}
@endif

Vous pouvez répondre directement à cet email.
</x-mail::message>
