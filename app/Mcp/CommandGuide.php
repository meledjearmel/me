<?php

namespace App\Mcp;

use App\Mcp\Servers\PortfolioServer;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;
use Laravel\Mcp\Server\Tool;
use ReflectionClass;

/**
 * Les commandes du serveur MCP pour la page « Serveur MCP » de l'administration : nom et
 * description lus sur le serveur, et conseils d'utilisation rédigés ici.
 *
 * Les prompts sont les commandes que l'on lance soi-même ; les outils sont appelés par
 * l'agent quand on lui demande quelque chose en langage naturel.
 */
class CommandGuide
{
    /**
     * Conseils par commande : quand s'en servir, comment l'utiliser au mieux, un exemple de
     * demande. Les commandes ont aussi des phrases à écrire telles quelles (requests), pour
     * claude.ai ou ChatGPT, mobile compris, où l'on ne tape pas de commande.
     *
     * @var array<string, array{usage: string, tips: list<string>, example: string, requests?: list<string>}>
     */
    private const array GUIDES = [
        // Prompts
        'draft-project' => [
            'usage' => 'Rédiger l\'étude de cas du projet ouvert dans Claude Code ou Codex, à partir de son code et de son historique git.',
            'tips' => [
                'Lancez-la depuis le dossier du projet : l\'agent lit le README, les manifestes, la structure du code et les remotes git.',
                'Ajoutez en notes ce que le code ne dit pas (client, contexte, chiffres, dates) : l\'agent posera moins de questions.',
                'Répondez à ses questions, ou dites « je ne sais pas » : il laisse vide plutôt que d\'inventer.',
                'Relisez le brouillon dans l\'admin, puis lancez translate-project une fois le français validé.',
            ],
            'example' => '/mcp__portfolio__draft-project notes="Client : mairie de Cocody, 3 mois en 2025, 2 000 utilisateurs"',
            'requests' => [
                'Rédige l\'étude de cas de ce projet pour mon portfolio. Client : mairie de Cocody, 3 mois en 2025, 2 000 utilisateurs.',
                'Crée le brouillon de projet de mon portfolio à partir du dépôt github.com/moi/mon-app.',
            ],
        ],
        'translate-project' => [
            'usage' => 'Traduire en anglais un brouillon de projet dont vous avez validé le français.',
            'tips' => [
                'Donnez le numéro du brouillon (affiché dans le message de création et dans l\'adresse de sa page admin).',
                'Les mentions @[…] sont gardées : seul leur libellé est traduit.',
                'Le brouillon n\'est pas publié : relisez l\'anglais puis publiez depuis l\'admin.',
            ],
            'example' => '/mcp__portfolio__translate-project id=12',
            'requests' => [
                'Traduis en anglais le brouillon de projet #12 de mon portfolio.',
                'Le français du projet #12 est validé, fais la version anglaise.',
            ],
        ],
        'write-post' => [
            'usage' => 'Écrire un brouillon d\'article du blog sur un sujet, en s\'appuyant sur le code ouvert et, au besoin, sur un article existant.',
            'tips' => [
                'Précisez l\'angle et le public dans le sujet (« pour des développeurs Laravel confirmés »).',
                'Donnez une adresse dans source_url pour vous inspirer d\'un article : il sera reformulé et cité, jamais recopié.',
                'Si le sujet prolonge une série, l\'agent lit les parties précédentes et place l\'article à la suite.',
                'La réponse finit par un plan d\'images avec des prompts de génération prêts à copier.',
            ],
            'example' => '/mcp__portfolio__write-post subject="Fiabiliser les files Laravel en production" source_url="https://…"',
            'requests' => [
                'Écris un brouillon d\'article pour mon blog sur la fiabilisation des files Laravel en production, pour des développeurs confirmés.',
                'Inspire-toi de cet article pour écrire un article de blog avec mon expérience : https://…',
            ],
        ],
        'plan-post-images' => [
            'usage' => 'Savoir quelles images mettre dans un brouillon d\'article, où, et comment les obtenir.',
            'tips' => [
                'Une capture réelle est toujours préférée ; l\'agent dit exactement quoi capturer.',
                'Pour une illustration, copiez le prompt anglais proposé dans votre générateur d\'images (format 16:9 pour la couverture).',
                'Renvoyez-lui ensuite les images (fichier ou adresse) : il les ajoute et les insère aux bons endroits.',
            ],
            'example' => '/mcp__portfolio__plan-post-images id=8',
            'requests' => [
                'Quelles images mettre dans le brouillon d\'article #8, et où ?',
                'Propose une couverture et des illustrations pour l\'article #8.',
            ],
        ],
        'plan-project-images' => [
            'usage' => 'Savoir quelle couverture et quelle galerie donner à un brouillon de projet.',
            'tips' => [
                'L\'agent propose les écrans clés dans l\'ordre du parcours et ce qu\'il faut flouter ou éviter.',
                'Utilisez des données de démonstration, jamais celles d\'un client.',
            ],
            'example' => '/mcp__portfolio__plan-project-images id=12',
            'requests' => [
                'Quelle couverture et quelles captures pour le brouillon de projet #12 ?',
                'Liste les écrans à capturer pour la galerie du projet #12.',
            ],
        ],
        'add-content' => [
            'usage' => 'Ajouter en brouillon une expérience, une formation, une certification, un élément « Uses », une compétence ou une offre de la page « Services » à partir d\'une source.',
            'tips' => [
                'La source peut être une adresse (page de certification, profil), un texte collé ou un fichier (CV).',
                'L\'agent vérifie d\'abord ce qui existe pour ne pas créer de doublon.',
            ],
            'example' => '/mcp__portfolio__add-content type=certification source="https://www.credly.com/badges/…"',
            'requests' => [
                'Ajoute cette certification à mon portfolio : https://www.credly.com/badges/…',
                'Ajoute mon expérience chez Orange à mon portfolio : développeur mobile depuis janvier 2025.',
            ],
        ],

        // Outils
        'get-catalog' => [
            'usage' => 'L\'agent découvre le site : technologies, domaines, profils, projets, articles, séries, tags, dépôts GitHub synchronisés, page « Now » et éléments à citer.',
            'tips' => ['Appelé en début de tâche ; inutile de le demander vous-même.'],
            'example' => 'Quels projets et articles sont déjà en ligne sur mon portfolio ?',
        ],
        'get-project' => [
            'usage' => 'Lire un projet (français, anglais, relations, images).',
            'tips' => ['Utile pour reprendre un brouillon ou s\'inspirer du ton d\'un projet publié.'],
            'example' => 'Relis le projet #12 et dis-moi ce qui manque à son étude de cas.',
        ],
        'create-technology' => [
            'usage' => 'Ajouter au site une technologie absente, avec sa description et son logo Iconify.',
            'tips' => ['Refusée si le nom existe déjà ; le logo reste à choisir dans l\'admin s\'il n\'est pas trouvé.'],
            'example' => 'Ajoute la technologie Laravel Reverb dans la catégorie Back-end.',
        ],
        'save-project-draft' => [
            'usage' => 'Créer un brouillon de projet en français (ou en remplacer un entièrement).',
            'tips' => [
                'Le dépôt GitHub est relié automatiquement, privé compris ; un dépôt GitLab est refusé avec son miroir GitHub proposé.',
                'Pour une petite correction, préférez update-project-draft.',
            ],
            'example' => 'Crée le brouillon du projet à partir de ce dépôt.',
        ],
        'update-project-draft' => [
            'usage' => 'Modifier seulement quelques champs d\'un brouillon de projet.',
            'tips' => ['Les autres champs et l\'anglais déjà traduit ne bougent pas.'],
            'example' => 'Dans le brouillon #12, le client est la mairie de Cocody.',
        ],
        'save-project-translation' => [
            'usage' => 'Enregistrer l\'anglais d\'un brouillon de projet.',
            'tips' => ['À demander une fois le français validé (voir translate-project).'],
            'example' => 'Traduis le brouillon #12 en anglais.',
        ],
        'add-project-image' => [
            'usage' => 'Ajouter la couverture ou une image de galerie à un brouillon de projet.',
            'tips' => ['Donnez un chemin de fichier local ou une adresse ; JPEG, PNG, WebP ou GIF de 5 Mo au plus.'],
            'example' => 'Mets docs/screens/accueil.png en couverture du brouillon #12.',
        ],
        'get-post' => [
            'usage' => 'Lire un article (contenu HTML, tags, série).',
            'tips' => ['Sert aussi à reprendre le ton d\'un article publié ou la suite d\'une série.'],
            'example' => 'Relis l\'article #8 et propose une conclusion plus forte.',
        ],
        'save-post-draft' => [
            'usage' => 'Créer un brouillon d\'article, ou modifier seulement certains de ses champs.',
            'tips' => ['Renvoie un lien d\'aperçu valable quelques heures.', 'Les tags existants sont réutilisés, les nouveaux créés et traduits.'],
            'example' => 'Ajoute le tag « Performance » au brouillon d\'article #8.',
        ],
        'save-post-translation' => [
            'usage' => 'Enregistrer l\'anglais d\'un brouillon d\'article.',
            'tips' => ['La structure HTML et les mentions sont gardées.'],
            'example' => 'Traduis le brouillon d\'article #8 en anglais.',
        ],
        'add-post-image' => [
            'usage' => 'Ajouter la couverture d\'un article, ou une image du contenu à insérer.',
            'tips' => ['Pour une image du contenu, l\'agent insère lui-même la balise renvoyée à l\'endroit prévu.'],
            'example' => 'Ajoute ce schéma après la partie « Le problème » du brouillon #8.',
        ],
        'read-web-page' => [
            'usage' => 'Lire une page web publique (article, doc, page de certification) pour s\'en inspirer.',
            'tips' => ['Les adresses internes sont refusées.', 'Rien n\'est recopié : l\'agent reformule et cite la source.'],
            'example' => 'Inspire-toi de https://… pour écrire un article sur le même sujet, avec mon expérience.',
        ],
        'list-contents' => [
            'usage' => 'Décrire les champs d\'un type de contenu et lister l\'existant.',
            'tips' => ['Types : experience, education, certification, uses_item, skill, service.'],
            'example' => 'Quelles certifications ai-je déjà sur le site ?',
        ],
        'get-content' => [
            'usage' => 'Lire une expérience, une formation, une certification, un élément « Uses », une compétence ou une offre.',
            'tips' => ['Renvoie le français et l\'anglais déjà saisi.'],
            'example' => 'Relis mon expérience #3 et reformule ses réalisations.',
        ],
        'save-content-draft' => [
            'usage' => 'Créer ou modifier en brouillon un de ces contenus, en français.',
            'tips' => ['Avec un id, seuls les champs envoyés changent.'],
            'example' => 'Ajoute mon expérience chez Orange (développeur mobile, depuis janvier 2025).',
        ],
        'save-content-translation' => [
            'usage' => 'Enregistrer l\'anglais d\'un de ces brouillons.',
            'tips' => ['Pour une expérience, les réalisations sont traduites dans le même ordre.'],
            'example' => 'Traduis le brouillon d\'expérience #3 en anglais.',
        ],
        'propose-revision' => [
            'usage' => 'Proposer une modification d\'un contenu déjà en ligne ou de la page « Now » ; rien ne change avant votre validation.',
            'tips' => [
                'Vous voyez l\'avant/après dans « Révisions IA » et appliquez ou rejetez.',
                'Le français et l\'anglais sont proposés ensemble, puisque le contenu est en ligne dans les deux langues.',
            ],
            'example' => 'Mets à jour ma page Now : je termine la v2 de mon portfolio et j\'apprends Rust.',
        ],
        'list-revisions' => [
            'usage' => 'Voir les révisions en attente de relecture.',
            'tips' => ['L\'agent s\'en sert pour ne pas proposer deux fois la même chose.'],
            'example' => 'Quelles modifications m\'as-tu déjà proposées ?',
        ],
        'get-estimator' => [
            'usage' => 'Lire la grille de l\'estimateur de projet (heures, facturation) et ses réglages (taux, marge, heures par semaine).',
            'tips' => ['Appelé avant de proposer un élément ou d\'analyser une demande, pour rester cohérent.'],
            'example' => 'Relis la grille de mon estimateur de projet et dis-moi ce qui te paraît sous-estimé.',
        ],
        'save-estimator-item' => [
            'usage' => 'Ajouter un type de projet ou une option à la grille de l\'estimateur, ou corriger ses heures.',
            'tips' => [
                'Un élément créé reste masqué aux visiteurs : vous l\'activez dans l\'admin après relecture.',
                'Les heures sont hors marge : le site ajoute lui-même la marge pour imprévus.',
            ],
            'example' => 'Ajoute à mon estimateur une option « Application mobile compagnon ».',
        ],
        'configure-estimator' => [
            'usage' => 'Configurer tout l\'estimateur en une fois : taux, marge, rythme, types de projet et options (en heures ou en pourcentage, liées ou non à des types).',
            'tips' => [
                'Décrivez votre activité et vos tarifs : l\'agent crée la grille complète en un seul appel.',
                'Rien n\'est enregistré si une partie est invalide ; les éléments créés restent masqués jusqu\'à leur activation.',
            ],
            'example' => 'Configure mon estimateur : sites vitrines, boutiques et apps mobiles, 100 CHF de l\'heure, 20 % de marge.',
        ],
        'list-estimate-requests' => [
            'usage' => 'Lire les demandes reçues par l\'estimateur : choix, estimation affichée, message et analyse existante.',
            'tips' => ['Les coordonnées du visiteur ne sont pas transmises à l\'agent.'],
            'example' => 'Quelles demandes d\'estimation n\'ai-je pas encore traitées ?',
        ],
        'save-estimate-analysis' => [
            'usage' => 'Enregistrer une analyse d\'une demande sur sa fiche : risques, points flous, questions, heures réalistes.',
            'tips' => [
                'Remplace l\'analyse faite automatiquement à la réception de la demande.',
                'Rien n\'est envoyé au client : vous gardez la main sur le devis.',
            ],
            'example' => 'Analyse la demande #42 et enregistre les questions à poser au client.',
        ],
        'list-job-applications' => [
            'usage' => 'Voir les pages de candidature, leur lien et qui les a consultées (visites, CV téléchargés).',
            'tips' => ['Avec un identifiant, l\'agent lit la page en entier et ses derniers événements.'],
            'example' => 'Quelles entreprises ont ouvert mes pages de candidature cette semaine ?',
        ],
        'save-job-application' => [
            'usage' => 'Préparer une page de candidature d\'après une offre d\'emploi : mot d\'accompagnement, projets et CV adaptés.',
            'tips' => [
                'Donnez le lien de l\'offre : l\'agent la lit, choisit vos projets les plus pertinents et rédige le mot dans sa langue.',
                'La page reste en brouillon : vous la relisez et la mettez en ligne dans l\'admin.',
                'Demandez aussi un CV adapté : l\'agent réécrit le titre et le résumé et choisit vos réalisations et compétences les plus pertinentes, sans rien inventer.',
            ],
            'example' => 'Prépare une page de candidature pour cette offre : https://exemple.com/offre-developpeur',
        ],
        'get-weekly-report' => [
            'usage' => 'Le bilan des 7 derniers jours : audience, demandes, estimations, candidatures consultées, blog et ce qui attend une action.',
            'tips' => ['Le même bilan part par email chaque lundi matin (réglable dans l\'admin, Rapport de la semaine).'],
            'example' => 'Comment s\'est passée ma semaine sur le portfolio ?',
        ],
        'save-post-social-texts' => [
            'usage' => 'Écrire les textes qui annoncent un article sur LinkedIn, X, Facebook et en statut WhatsApp.',
            'tips' => ['Rien n\'est publié : sur la fiche de l\'article, un clic ouvre chaque réseau avec le texte et le lien déjà remplis.'],
            'example' => 'Prépare les textes de partage de mon dernier article pour LinkedIn, X, Facebook et WhatsApp.',
        ],
        'syndicate-post' => [
            'usage' => 'Republier un article publié sur dev.to ou Hashnode, avec un lien canonique vers votre site.',
            'tips' => [
                'Publie réellement : l\'agent ne l\'utilise que si vous le demandez pour cet article.',
                'La plateforme doit être configurée dans l\'admin (Blog, Republication) ; republier met à jour la version existante.',
            ],
            'example' => 'Republie mon dernier article sur dev.to.',
        ],
    ];

    /**
     * Titre court de chaque commande, pour la lire d'un coup d'œil.
     *
     * @var array<string, string>
     */
    private const array TITLES = [
        'draft-project' => 'Rédiger un projet',
        'translate-project' => 'Traduire un projet',
        'write-post' => 'Écrire un article',
        'plan-post-images' => 'Images d\'un article',
        'plan-project-images' => 'Images d\'un projet',
        'add-content' => 'Ajouter un contenu',
        'get-catalog' => 'Explorer le site',
        'get-project' => 'Lire un projet',
        'create-technology' => 'Ajouter une technologie',
        'save-project-draft' => 'Créer un brouillon de projet',
        'update-project-draft' => 'Corriger un brouillon de projet',
        'save-project-translation' => 'Traduire un brouillon de projet',
        'add-project-image' => 'Image d\'un projet',
        'get-post' => 'Lire un article',
        'save-post-draft' => 'Brouillon d\'article',
        'save-post-translation' => 'Traduire un article',
        'add-post-image' => 'Image d\'un article',
        'read-web-page' => 'Lire une page web',
        'list-contents' => 'Lister des contenus',
        'get-content' => 'Lire un contenu',
        'save-content-draft' => 'Brouillon de contenu',
        'save-content-translation' => 'Traduire un contenu',
        'propose-revision' => 'Proposer une modification',
        'list-revisions' => 'Révisions en attente',
        'get-estimator' => 'Lire l\'estimateur',
        'save-estimator-item' => 'Élément de l\'estimateur',
        'configure-estimator' => 'Configurer l\'estimateur',
        'list-estimate-requests' => 'Demandes d\'estimation',
        'save-estimate-analysis' => 'Analyser une demande',
        'list-job-applications' => 'Pages de candidature',
        'save-job-application' => 'Préparer une candidature',
        'get-weekly-report' => 'Bilan de la semaine',
        'save-post-social-texts' => 'Textes de partage',
        'syndicate-post' => 'Republier un article',
    ];

    /** Titre court d'une commande ou d'un outil (son nom s'il n'en a pas). */
    public static function title(string $name): string
    {
        return self::TITLES[$name] ?? $name;
    }

    /**
     * @return array{prompts: list<array<string, mixed>>, tools: list<array<string, mixed>>}
     */
    public function all(): array
    {
        $defaults = (new ReflectionClass(PortfolioServer::class))->getDefaultProperties();

        return [
            'prompts' => array_map(fn (string $class): array => $this->describe(app($class), 'prompt'), $defaults['prompts']),
            'tools' => array_map(fn (string $class): array => $this->describe(app($class), 'tool'), $defaults['tools']),
        ];
    }

    /** @return array<string, mixed> */
    private function describe(Tool|Prompt $primitive, string $kind): array
    {
        $name = $primitive->name();
        $guide = self::GUIDES[$name] ?? ['usage' => '', 'tips' => [], 'example' => ''];

        // L'exemple d'un outil est déjà une phrase à écrire.
        $guide['requests'] ??= $kind === 'tool' && $guide['example'] !== '' ? [$guide['example']] : [];

        return [
            'name' => $name,
            'title' => self::TITLES[$name] ?? $name,
            'kind' => $kind,
            'description' => $primitive->description(),
            'arguments' => $primitive instanceof Prompt
                ? array_map(fn (Argument $argument): array => [
                    'name' => $argument->name,
                    'description' => $argument->description,
                    'required' => $argument->required,
                ], $primitive->arguments())
                : [],
            ...$guide,
        ];
    }
}
