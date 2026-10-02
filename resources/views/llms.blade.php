# {!! $profile->name !!}

> {!! $profile->getTranslation('headline', $locale) !!}. {!! $profile->getTranslation('bio_short', $locale) !!}

@if ($profile->location)
- Localisation : {!! $profile->location !!}
@endif
- Contact : {!! $baseUrl !!}/{!! $locale !!}/contact
@foreach ($profile->social_links ?? [] as $network => $link)
@if ($link)
- {!! ucfirst($network) !!} : {!! $link !!}
@endif
@endforeach

## Pages

- [Accueil]({!! $baseUrl !!}/{!! $locale !!}) : présentation et projets phares
- [À propos]({!! $baseUrl !!}/{!! $locale !!}/about) : parcours, expériences et formations
- [Compétences]({!! $baseUrl !!}/{!! $locale !!}/skills) : domaines d'expertise et technologies
- [Projets]({!! $baseUrl !!}/{!! $locale !!}/projects) : réalisations détaillées
- [Témoignages]({!! $baseUrl !!}/{!! $locale !!}/testimonials) : avis de clients et collègues
- [English version]({!! $baseUrl !!}/en) : the same content in English

@if ($projects->isNotEmpty())
## Projets

@foreach ($projects as $project)
- [{!! $project->getTranslation('title', $locale) !!}]({!! $baseUrl !!}/{!! $locale !!}/projects/{!! $project->slug !!}) : {!! str($project->getTranslation('result', $locale))->squish()->limit(200) !!}
@endforeach
@endif
