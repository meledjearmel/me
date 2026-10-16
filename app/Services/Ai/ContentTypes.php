<?php

namespace App\Services\Ai;

use App\Enums\CertificationKind;
use App\Enums\ServicePriceMode;
use App\Enums\UsesCategory;
use App\Models\Certification;
use App\Models\Distinction;
use App\Models\Education;
use App\Models\EstimatorItem;
use App\Models\Experience;
use App\Models\Service;
use App\Models\Skill;
use App\Models\UsesItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Les contenus du portfolio que le serveur MCP rédige en brouillon (en plus des projets et
 * des articles, qui ont leurs propres outils). Pour chaque type : modèle, champs traduits
 * (écrits en français, puis traduits), champs simples, et relations à part.
 *
 * Chaque champ porte ses règles de validation et une description destinée à l'agent.
 */
class ContentTypes
{
    /**
     * @return array<string, array{model: class-string<Model>, label: string, route: string, title: string, mentions: list<string>, translatable: array<string, array{rules: array<int, mixed>, doc: string}>, fields: array<string, array{rules: array<int, mixed>, doc: string}>, relations: array<string, array{rules: array<string, array<int, mixed>>, doc: string}>}>
     */
    public function all(): array
    {
        return [
            'experience' => [
                'model' => Experience::class,
                'label' => 'expérience professionnelle',
                'route' => 'admin.experiences.edit',
                'title' => 'company',
                'mentions' => ['description'],
                'translatable' => [
                    'role' => ['rules' => ['required', 'string', 'max:255'], 'doc' => 'Poste occupé, ex. « Développeur full-stack ».'],
                    'description' => ['rules' => ['nullable', 'string'], 'doc' => 'Missions et contexte, 1 à 3 paragraphes en texte brut ; peut citer un élément du site avec son jeton de mention.'],
                ],
                'fields' => [
                    'company' => ['rules' => ['required', 'string', 'max:255'], 'doc' => 'Entreprise ou client.'],
                    'location' => ['rules' => ['nullable', 'string', 'max:255'], 'doc' => 'Ville, pays ou « À distance ».'],
                    'start_date' => ['rules' => ['required', 'date_format:Y-m-d'], 'doc' => 'Début, AAAA-MM-JJ (le 1er du mois si le jour est inconnu).'],
                    'end_date' => ['rules' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'], 'doc' => 'Fin, AAAA-MM-JJ ; vide si en cours.'],
                ],
                'relations' => [
                    'highlights' => ['rules' => ['highlights' => ['nullable', 'array', 'max:8'], 'highlights.*' => ['string', 'max:300']], 'doc' => 'Réalisations marquantes, une phrase chacune, dans l\'ordre (liste complète, 8 au plus).'],
                ],
            ],
            'education' => [
                'model' => Education::class,
                'label' => 'formation',
                'route' => 'admin.educations.edit',
                'title' => 'institution',
                'mentions' => [],
                'translatable' => [
                    'degree' => ['rules' => ['required', 'string', 'max:255'], 'doc' => 'Diplôme, ex. « Master ».'],
                    'field' => ['rules' => ['required', 'string', 'max:255'], 'doc' => 'Spécialité, ex. « Génie logiciel ».'],
                    'description' => ['rules' => ['nullable', 'string'], 'doc' => 'Ce qui a été appris ou réalisé, en texte brut.'],
                ],
                'fields' => [
                    'institution' => ['rules' => ['required', 'string', 'max:255'], 'doc' => 'Établissement.'],
                    'start_date' => ['rules' => ['required', 'date_format:Y-m-d'], 'doc' => 'Début, AAAA-MM-JJ.'],
                    'end_date' => ['rules' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'], 'doc' => 'Fin, AAAA-MM-JJ ; vide si en cours.'],
                ],
                'relations' => [],
            ],
            'certification' => [
                'model' => Certification::class,
                'label' => 'certification ou formation courte',
                'route' => 'admin.certifications.edit',
                'title' => 'issuer',
                'mentions' => [],
                'translatable' => [
                    'name' => ['rules' => ['required', 'string', 'max:160'], 'doc' => 'Intitulé de la certification ou du cours.'],
                ],
                'fields' => [
                    'kind' => ['rules' => ['required', Rule::enum(CertificationKind::class)], 'doc' => '« certification » ou « course » (formation suivie).'],
                    'issuer' => ['rules' => ['required', 'string', 'max:120'], 'doc' => 'Organisme, ex. « AWS », « Coursera ».'],
                    'issued_on' => ['rules' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'doc' => 'Date d\'obtention, AAAA-MM-JJ.'],
                    'expires_on' => ['rules' => ['nullable', 'date_format:Y-m-d', 'after:issued_on'], 'doc' => 'Expiration, AAAA-MM-JJ ; vide si sans limite.'],
                    'credential_id' => ['rules' => ['nullable', 'string', 'max:120'], 'doc' => 'Identifiant du certificat.'],
                    'credential_url' => ['rules' => ['nullable', 'url', 'max:255'], 'doc' => 'Lien de vérification.'],
                ],
                'relations' => [],
            ],
            'distinction' => [
                'model' => Distinction::class,
                'label' => 'distinction ou prix reçu (carte « Distinction » de la page À propos)',
                'route' => 'admin.distinctions.edit',
                'title' => 'issuer',
                'mentions' => [],
                'translatable' => [
                    'title' => ['rules' => ['required', 'string', 'max:160'], 'doc' => 'Intitulé de la distinction, ex. « Meilleur agent du premier semestre 2025 ».'],
                    'description' => ['rules' => ['nullable', 'string', 'max:500'], 'doc' => 'Contexte en une ou deux phrases (facultatif).'],
                ],
                'fields' => [
                    'issuer' => ['rules' => ['required', 'string', 'max:120'], 'doc' => 'Organisme qui l\'a décernée, ex. « CIAPOL ».'],
                    'awarded_on' => ['rules' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'doc' => 'Date d\'obtention, AAAA-MM-JJ.'],
                    'url' => ['rules' => ['nullable', 'url', 'max:255'], 'doc' => 'Lien vers une preuve (article, publication).'],
                    'context_type' => ['rules' => ['nullable', 'required_with:context_id', Rule::in(array_keys(Distinction::CONTEXTS))], 'doc' => 'Contexte facultatif : « experience », « education » ou « project » (identifiants avec list-contents ou get-catalog).'],
                    'context_id' => ['rules' => ['nullable', 'required_with:context_type', 'integer'], 'doc' => 'Identifiant de l\'expérience, de la formation ou du projet.'],
                ],
                'relations' => [],
            ],
            'uses_item' => [
                'model' => UsesItem::class,
                'label' => 'élément de la page « Uses » (matériel, outil, application, service)',
                'route' => 'admin.uses-items.edit',
                'title' => 'name',
                'mentions' => [],
                'translatable' => [
                    'description' => ['rules' => ['nullable', 'string', 'max:300'], 'doc' => 'Pourquoi je l\'utilise, en une ou deux phrases (300 caractères max.).'],
                ],
                'fields' => [
                    'category' => ['rules' => ['required', Rule::enum(UsesCategory::class)], 'doc' => '« hardware », « development », « apps » ou « services ».'],
                    'name' => ['rules' => ['required', 'string', 'max:120'], 'doc' => 'Nom du produit ou de l\'outil.'],
                    'url' => ['rules' => ['nullable', 'url', 'max:255'], 'doc' => 'Site officiel.'],
                ],
                'relations' => [],
            ],
            'service' => [
                'model' => Service::class,
                'label' => 'offre de la page « Services »',
                'route' => 'admin.services.edit',
                'title' => 'slug',
                'mentions' => ['description'],
                'translatable' => [
                    'title' => ['rules' => ['required', 'string', 'max:120'], 'doc' => 'Nom de l\'offre, court, ex. « Application mobile ».'],
                    'tagline' => ['rules' => ['nullable', 'string', 'max:200'], 'doc' => 'Accroche d\'une phrase : le bénéfice pour le client.'],
                    'description' => ['rules' => ['nullable', 'string', 'max:3000'], 'doc' => 'Pour qui, quel problème elle règle et comment je travaille, 1 à 2 paragraphes en texte brut ; peut citer un élément du site avec son jeton de mention.'],
                    'includes' => ['rules' => ['nullable', 'string', 'max:2000'], 'doc' => 'Ce qui est compris, un élément par ligne (5 à 8 lignes courtes).'],
                ],
                'fields' => [
                    'slug' => ['rules' => ['required', 'string', 'max:120', 'alpha_dash:ascii'], 'doc' => 'Identifiant d\'ancre en minuscules, ex. « application-mobile » (unique).'],
                    'price_mode' => ['rules' => ['required', Rule::enum(ServicePriceMode::class)], 'doc' => '« estimator » (prix calculé depuis le type de projet de l\'estimateur, voir get-estimator), « manual » (montants dans manual_prices) ou « quote » (sur devis).'],
                    'estimator_item_id' => ['rules' => ['nullable', 'required_if:price_mode,estimator', 'integer', Rule::exists('estimator_items', 'id')->where('kind', 'project_type')->whereNull('deleted_at')], 'doc' => 'Type de projet de l\'estimateur lié (get-estimator) : prix calculé et lien « Estimer mon projet » ; utile même hors du mode estimator.'],
                    'manual_prices' => ['rules' => ['nullable', 'required_if:price_mode,manual', 'array:'.implode(',', EstimatorItem::CURRENCIES)], 'doc' => 'Prix « à partir de » par devise, ex. {"CHF": 2500, "EUR": 2400} (mode manual).'],
                ],
                'relations' => [
                    'projects' => ['rules' => ['projects' => ['nullable', 'array', 'max:6'], 'projects.*' => ['integer', 'exists:projects,id']], 'doc' => 'Identifiants des projets qui illustrent l\'offre (get-catalog), liste complète, 6 au plus ; leurs avis publiés s\'affichent avec l\'offre.'],
                ],
            ],
            'skill' => [
                'model' => Skill::class,
                'label' => 'compétence',
                'route' => 'admin.skills.edit',
                'title' => 'name',
                'mentions' => [],
                'translatable' => [
                    'name' => ['rules' => ['required', 'string', 'max:255'], 'doc' => 'Nom de la compétence.'],
                    'description' => ['rules' => ['nullable', 'string'], 'doc' => 'Résumé d\'une phrase.'],
                    'details' => ['rules' => ['nullable', 'string'], 'doc' => 'Détail : ce que je sais faire, avec des exemples concrets.'],
                ],
                'fields' => [
                    'domain_id' => ['rules' => ['required', 'integer', 'exists:domains,id'], 'doc' => 'Domaine (identifiant, voir domains de get-catalog).'],
                ],
                'relations' => [
                    'technologies' => ['rules' => ['technologies' => ['nullable', 'array'], 'technologies.*' => ['integer', 'exists:technologies,id']], 'doc' => 'Identifiants des technologies liées (get-catalog), liste complète.'],
                ],
            ],
        ];
    }

    /**
     * @return array{model: class-string<Model>, label: string, route: string, title: string, mentions: list<string>, translatable: array<string, array{rules: array<int, mixed>, doc: string}>, fields: array<string, array{rules: array<int, mixed>, doc: string}>, relations: array<string, array{rules: array<string, array<int, mixed>>, doc: string}>}
     *
     * @throws InvalidArgumentException
     */
    public function get(string $type): array
    {
        return $this->all()[$type] ?? throw new InvalidArgumentException("Type de contenu inconnu : {$type}. Types : ".implode(', ', array_keys($this->all())).'.');
    }

    /**
     * Les champs d'un type, décrits pour l'agent.
     *
     * @return array<string, string>
     */
    public function documentation(string $type): array
    {
        $definition = $this->get($type);
        $required = fn (array $rules): string => in_array('required', $rules, true) ? ' (obligatoire)' : '';

        return [
            ...collect($definition['fields'])->map(fn (array $field): string => $field['doc'].$required($field['rules']))->all(),
            ...collect($definition['translatable'])->map(fn (array $field): string => 'En français. '.$field['doc'].$required($field['rules']))->all(),
            ...collect($definition['relations'])->map(fn (array $relation): string => $relation['doc'])->all(),
        ];
    }
}
