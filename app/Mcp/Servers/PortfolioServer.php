<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\AddContentPrompt;
use App\Mcp\Prompts\DraftProjectPrompt;
use App\Mcp\Prompts\PlanPostImagesPrompt;
use App\Mcp\Prompts\PlanProjectImagesPrompt;
use App\Mcp\Prompts\TranslateProjectPrompt;
use App\Mcp\Prompts\WritePostPrompt;
use App\Mcp\Tools\AddPostImageTool;
use App\Mcp\Tools\AddProjectImageTool;
use App\Mcp\Tools\ConfigureEstimatorTool;
use App\Mcp\Tools\CreateTechnologyTool;
use App\Mcp\Tools\DecideAppointmentTool;
use App\Mcp\Tools\DraftQuoteTool;
use App\Mcp\Tools\GetCatalogTool;
use App\Mcp\Tools\GetContentTool;
use App\Mcp\Tools\GetEstimatorTool;
use App\Mcp\Tools\GetInboxTool;
use App\Mcp\Tools\GetPostTool;
use App\Mcp\Tools\GetProjectTool;
use App\Mcp\Tools\GetStatsTool;
use App\Mcp\Tools\GetWeeklyReportTool;
use App\Mcp\Tools\ListCommercialDocumentsTool;
use App\Mcp\Tools\ListContentsTool;
use App\Mcp\Tools\ListEstimateRequestsTool;
use App\Mcp\Tools\ListJobApplicationsTool;
use App\Mcp\Tools\ListRevisionsTool;
use App\Mcp\Tools\MarkHandledTool;
use App\Mcp\Tools\ModerateCommentTool;
use App\Mcp\Tools\ModerateTestimonialTool;
use App\Mcp\Tools\ProposeRevisionTool;
use App\Mcp\Tools\ReadWebPageTool;
use App\Mcp\Tools\SaveContentDraftTool;
use App\Mcp\Tools\SaveContentTranslationTool;
use App\Mcp\Tools\SaveEstimateAnalysisTool;
use App\Mcp\Tools\SaveEstimatorItemTool;
use App\Mcp\Tools\SaveJobApplicationTool;
use App\Mcp\Tools\SavePostDraftTool;
use App\Mcp\Tools\SavePostSocialTextsTool;
use App\Mcp\Tools\SavePostTranslationTool;
use App\Mcp\Tools\SaveProjectDraftTool;
use App\Mcp\Tools\SaveProjectTranslationTool;
use App\Mcp\Tools\SyndicatePostTool;
use App\Mcp\Tools\UpdateProjectDraftTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Serveur MCP du portfolio, pour Claude Code, Codex ou Claude Desktop : l'agent explore
 * lui-même un dépôt (privé compris, en local) et enregistre un brouillon de projet ou
 * d'article en français, que le développeur relit, fait traduire et publie dans l'administration.
 */
#[Name('Portfolio')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Ce serveur alimente le portfolio d'un développeur web. Pour rédiger l'étude de cas d'un projet :
1. Explore la source (dépôt, notes) : README, manifestes (composer.json, package.json, pubspec.yaml…), structure du code, historique des commits. Lis aussi les remotes (« git remote -v ») : le portfolio ne relie que des dépôts GitHub. Passe l'adresse GitHub telle quelle dans github_repository (le site relie ainsi le projet à son dépôt, privé compris, et à son activité). Si le dépôt est sur GitLab ou ailleurs (souvent avec un miroir sur GitHub), cherche dans github_repositories de get-catalog un dépôt au nom équivalent et fais-le confirmer au développeur ; sans équivalent, demande-lui ou laisse le champ vide. Ne passe jamais une adresse qui n'est pas sur GitHub. Si get-catalog montre ce dépôt déjà lié à un projet (linked_project_id), il s'agit sans doute d'une mise à jour de ce projet : demande au développeur avant de créer un doublon.
Avant d'écrire, si la source laisse des trous importants (client ou contexte, ton rôle exact, période, résultat mesurable, public visé, ce qui est confidentiel), pose au développeur quelques questions courtes et groupées (cinq au plus, en une seule fois), puis attends ses réponses. Ne demande pas ce que le code ou l'historique git dit déjà. S'il ne sait pas ou préfère passer, laisse le champ vide plutôt que d'inventer.
2. Appelle get-catalog pour connaître les technologies, domaines et profils métier existants et les slugs déjà pris. Tu peux lire un projet publié avec get-project pour reprendre le ton.
3. Appelle save-project-draft avec les champs en français. Ton sobre et concret, à la première personne, paragraphes en texte brut séparés par une ligne vide, sans Markdown.
Pour citer une autre app, un article, une technologie ou une expérience du site dans le contexte, les défis, la réalisation ou le résultat, insère son jeton de mention tel que get-catalog le donne (ex. « @[App Station](project:12) ») : il devient un lien avec une carte au survol. Si le projet forme un ensemble avec d'autres (app et back-office, nouvelle version, app compagnon), renseigne aussi related_projects.
Pour corriger un brouillon existant, utilise update-project-draft avec seulement les champs à changer.
Images d'un projet : add-project-image (couverture, galerie). Sans image fournie, propose un plan d'images (quoi capturer, ou un prompt de génération professionnel) ; voir le prompt plan-project-images.
Si une technologie importante manque au site, crée-la avec create-technology avant d'enregistrer le brouillon.
Pour un article du blog : get-catalog (tags, séries avec leurs articles dans l'ordre, éléments à citer), puis save-post-draft avec le contenu en HTML simple ; save-post-draft avec un id modifie seulement les champs envoyés.
- Séries : si le sujet prolonge une série existante (post_series), lis ses articles (get-post) pour assurer la continuité (rappel de la partie précédente, pas de redite) et place l'article à next_position ; propose une nouvelle série seulement si le développeur prévoit plusieurs parties.
- Inspiration : read-web-page lit un article existant donné par le développeur. Ne recopie jamais : reformule, apporte son expérience et son code, et cite la source avec un lien quand une idée en vient.
- Images : add-post-image ajoute la couverture ou une image du contenu (capture, schéma), puis insère la balise <img> renvoyée dans body. Sans image fournie, propose un plan d'images : emplacement, type (capture réelle de préférence, schéma, illustration), utilité, et pour les images à générer un prompt professionnel en anglais prêt à copier (format, style sobre et cohérent, sans texte incrusté) ; voir le prompt plan-post-images.
Autres contenus (expériences, formations, certifications, page « Uses », compétences, offres de la page « Services ») : list-contents décrit les champs du type et liste l'existant (pas de doublon), get-content lit un contenu, save-content-draft crée ou modifie un brouillon en français ; voir le prompt add-content.
Ne traduis en anglais (save-project-translation, save-post-translation, save-content-translation) que lorsque le développeur a validé le français et te le demande.
Contenu déjà publié (ou page « Now ») : ne le modifie jamais directement. Lis-le, vérifie list-revisions pour ne pas proposer deux fois la même chose, puis propose-revision avec seulement les champs modifiés, en français et en anglais, et un résumé de ce qui change et pourquoi ; le développeur relit l'avant/après et applique.
Estimateur de projet : get-estimator lit la grille (heures de travail hors marge, facturation forfait ou à l'heure) et les réglages (taux horaire par devise, marge pour imprévus, heures par semaine). Pour tout configurer d'un coup (réglages et plusieurs éléments), utilise configure-estimator : donne une ref à chaque nouveau type de projet pour qu'une option le vise dans project_types (option réservée à ces types ; vide = pour tous) ; une option dont le coût grandit avec le projet (multilingue…) se chiffre en pourcentage (pricing « percent »). save-estimator-item ajoute ou corrige un seul élément. Les éléments créés restent masqués jusqu'à ce que le développeur les active ; chiffre des heures réalistes pour un développeur expérimenté, cohérentes avec le reste de la grille. Pour une demande reçue : list-estimate-requests, puis save-estimate-analysis ; l'objectif est d'éviter une sous-estimation (ce que les choix ne couvrent pas, points flous, questions à poser au client, heures marge comprise, « hourly » quand le périmètre est trop flou pour un forfait sûr).
Candidature : pour une offre d'emploi donnée par le développeur, lis-la (read-web-page), regarde list-job-applications (pas de doublon pour la même entreprise), le portfolio (get-catalog, get-project), puis save-job-application : un mot d'accompagnement dans la langue de l'offre, appuyé sur des projets réels cités avec leur jeton de mention de get-catalog (jamais un identifiant seul dans le texte), et 2 à 4 projets du plus pertinent au moins pertinent. Pour un CV adapté à l'offre, relis la page avec list-job-applications (id) pour avoir cv_material, puis envoie cv_title, cv_summary, cv_experiences et cv_skill_ids : uniquement des réalisations et compétences existantes, choisies et ordonnées, jamais de nouvelle. La page reste en brouillon : le développeur la relit et la met en ligne.
Faire connaître un article : save-post-social-texts enregistre ses textes de partage (LinkedIn, X, Facebook, statut WhatsApp), que le développeur publie lui-même ; syndicate-post le republie sur dev.to ou Hashnode, mais seulement à sa demande explicite (c'est une publication réelle).
Suivi du site : get-inbox liste ce qui attend une réponse (messages, demandes, rendez-vous, avis, commentaires), get-stats et get-weekly-report donnent les chiffres. decide-appointment (le visiteur reçoit un email), moderate-testimonial et moderate-comment (publication immédiate) agissent pour de vrai : seulement sur demande explicite, élément par élément ; mark-handled range ce dont le développeur dit s'être occupé, et enregistre qu'une demande est signée (avec le montant qu'il donne) ou perdue.
Devis et factures : list-commercial-documents suit les devis, proformas et factures (émis, acceptés, à encaisser). draft-quote prépare un devis en brouillon depuis une demande, avec les lignes de son estimation ou celles que le développeur valide ; tu n'émets ni n'envoies jamais un document : il le fait lui-même depuis l'administration.
N'invente rien : client, dates, chiffres ou taille d'équipe absents de la source restent vides. Ne cite ni secret ni donnée confidentielle d'un client. Rien n'est jamais publié par toi : ce sont toujours des brouillons.
TEXT)]
class PortfolioServer extends Server
{
    /** Tous les outils sur une seule page : certains clients ne demandent pas la suite. */
    public int $defaultPaginationLength = 50;

    protected array $tools = [
        GetCatalogTool::class,
        GetProjectTool::class,
        CreateTechnologyTool::class,
        SaveProjectDraftTool::class,
        UpdateProjectDraftTool::class,
        SaveProjectTranslationTool::class,
        GetPostTool::class,
        SavePostDraftTool::class,
        SavePostTranslationTool::class,
        AddPostImageTool::class,
        AddProjectImageTool::class,
        ReadWebPageTool::class,
        ListContentsTool::class,
        GetContentTool::class,
        SaveContentDraftTool::class,
        SaveContentTranslationTool::class,
        ProposeRevisionTool::class,
        ListRevisionsTool::class,
        GetEstimatorTool::class,
        SaveEstimatorItemTool::class,
        ConfigureEstimatorTool::class,
        ListEstimateRequestsTool::class,
        SaveEstimateAnalysisTool::class,
        ListJobApplicationsTool::class,
        SaveJobApplicationTool::class,
        GetWeeklyReportTool::class,
        SavePostSocialTextsTool::class,
        SyndicatePostTool::class,
        GetInboxTool::class,
        GetStatsTool::class,
        DecideAppointmentTool::class,
        ModerateTestimonialTool::class,
        ModerateCommentTool::class,
        MarkHandledTool::class,
        ListCommercialDocumentsTool::class,
        DraftQuoteTool::class,
    ];

    protected array $prompts = [
        AddContentPrompt::class,
        DraftProjectPrompt::class,
        TranslateProjectPrompt::class,
        WritePostPrompt::class,
        PlanPostImagesPrompt::class,
        PlanProjectImagesPrompt::class,
    ];
}
