<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Models\Domain;
use App\Models\JobProfile;
use App\Models\Project;
use App\Models\Technology;
use Illuminate\Database\Seeder;

/**
 * Projets réels, tirés des fiches de `docs/fiches`. Les clients sont anonymisés.
 */
class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->projects() as $index => $data) {
            $project = Project::query()->create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'context' => $data['context'],
                'realization' => $data['realization'],
                'result' => $data['result'],
                'accent_color' => $data['accent'],
                'is_featured' => $data['featured'] ?? false,
                'is_open_source' => $data['open_source'] ?? false,
                'status' => ProjectStatus::Published,
                'sort_order' => $index + 1,
            ]);

            $project->domains()->attach(Domain::query()->whereIn('key', $data['domains'])->pluck('id'));
            $project->jobProfiles()->attach(JobProfile::query()->whereIn('key', $data['jobs'])->pluck('id'));
            $project->technologies()->attach(Technology::query()->whereIn('name', $data['tech'])->pluck('id'));
        }

        $this->relate('app-station', 'registra');
        $this->relate('gesmar', 'gesmar-verif');
        $this->relate('aps-connect', 'app-station');
        $this->relate('aps-connect', 'laravel-app-pairing');
    }

    /** Liaison symétrique : les deux sens sont attachés. */
    private function relate(string $slug, string $otherSlug): void
    {
        $project = Project::query()->where('slug', $slug)->firstOrFail();
        $other = Project::query()->where('slug', $otherSlug)->firstOrFail();

        $project->relatedProjects()->syncWithoutDetaching([$other->id]);
        $other->relatedProjects()->syncWithoutDetaching([$project->id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function projects(): array
    {
        $laravel = ['PHP', 'Laravel', 'Livewire', 'Pest'];

        return [
            [
                'slug' => 'app-station',
                'title' => ['fr' => 'App Station', 'en' => 'App Station'],
                'featured' => true,
                'accent' => '#3456c8',
                'domains' => ['dev', 'securite'],
                'jobs' => ['full-stack', 'lead-tech'],
                'tech' => [...$laravel, 'Redis', 'Vite', 'TailwindCSS', 'Fortify (2FA)'],
                'context' => [
                    'fr' => 'Une place de marché pour distribuer des logiciels et gérer leurs licences, ouverte à des éditeurs tiers : catalogue, versions, téléchargements, achats et avis, avec trois publics (administrateurs, éditeurs, clients).',
                    'en' => 'A marketplace to distribute software and manage its licences, open to third-party publishers: catalogue, releases, downloads, purchases and reviews, for three audiences (administrators, publishers, customers).',
                ],
                'realization' => [
                    'fr' => 'Espace éditeur avec équipe et 11 permissions déléguées, validation des éditeurs et des logiciels avec historique, releases par canal (stable, bêta) et par plateforme, suivi des instances installées, achat en ligne avec réconciliation automatique des paiements, double authentification (e-mail) et passkeys. Le tout repose sur 24 modèles, 75 composants Livewire et une API REST versionnée.',
                    'en' => 'Publisher area with a team and 11 delegated permissions, publisher and software validation with history, releases per channel (stable, beta) and platform, tracking of installed instances, online purchase with automatic payment reconciliation, email two-factor authentication and passkeys. Built on 24 models, 75 Livewire components and a versioned REST API.',
                ],
                'result' => [
                    'fr' => "En production, avec plus de 630 tests automatisés. Une séparation stricte empêche les comptes de démonstration d'atteindre la production.",
                    'en' => 'In production, with over 630 automated tests. A strict split keeps demo accounts out of production.',
                ],
            ],
            [
                'slug' => 'registra',
                'title' => ['fr' => 'Registra', 'en' => 'Registra'],
                'accent' => '#7c4dcc',
                'domains' => ['dev', 'securite'],
                'jobs' => ['full-stack', 'lead-tech'],
                'tech' => [...$laravel, 'Redis', 'Vite', 'TailwindCSS', 'Fortify (2FA)'],
                'context' => [
                    'fr' => "Un service de vérification de licences logicielles avec sa boutique : les logiciels tiers l'interrogent par API pour savoir, en temps réel, si une licence est valide.",
                    'en' => 'A software-licence verification service with its own shop: third-party software queries it through an API to know, in real time, whether a licence is valid.',
                ],
                'realization' => [
                    'fr' => 'Licences avec modules activables, contrôle du nombre de postes par licence, campagnes promotionnelles et licences à vie, rotation des clés API sans coupure (clé actuelle et précédente), raisons de refus explicites pour faciliter le support, connexion sociale et passkeys. Déploiement continu GitLab CI vers un serveur Linux (migrations et rechargement inclus).',
                    'en' => 'Licences with switchable modules, per-licence device limits, promotional campaigns and lifetime licences, API-key rotation without downtime (current and previous key), explicit refusal reasons to ease support, social login and passkeys. Continuous deployment with GitLab CI to a Linux server (migrations and cache refresh included).',
                ],
                'result' => [
                    'fr' => 'En production, 76 migrations et 126 fichiers de tests. Il est branché sur App Station, qui lui délègue la vérification des licences.',
                    'en' => 'In production, with 76 migrations and 126 test files. It is wired to App Station, which delegates licence checks to it.',
                ],
            ],
            [
                'slug' => 'gesmar',
                'title' => ['fr' => 'GESMAR', 'en' => 'GESMAR'],
                'featured' => true,
                'accent' => '#0f8f8a',
                'domains' => ['dev', 'securite'],
                'jobs' => ['full-stack', 'chef-projet'],
                'tech' => ['PHP', 'Laravel', 'Livewire', 'TailwindCSS', 'Alpine.js', 'PostgreSQL', 'Pest', 'Fortify (2FA)'],
                'context' => [
                    'fr' => "Gestion et sécurisation des certificats médicaux d'aptitude à la navigation maritime pour une administration maritime nationale : du paiement du marin jusqu'à la vérification du certificat par QR code.",
                    'en' => "Management and securing of medical fitness certificates for sea service, for a national maritime authority: from the seafarer's payment to certificate verification by QR code.",
                ],
                'realization' => [
                    'fr' => "Circuit complet : accueil, examens médicaux par département, décision d'aptitude, impression sécurisée sur feuilles pré-numérotées, signature de l'autorité et remise. Inventaire des lots de documents avec transferts tracés et registre de destruction à double validation, 9 profils métier, journal d'audit interne, plan de sauvegarde RPO/RTO.",
                    'en' => 'Full workflow: reception, medical exams by department, fitness decision, secure printing on pre-numbered sheets, authority signature and handover. Document-batch inventory with traced transfers and a destruction register with double validation, 9 business roles, an internal audit log and an RPO/RTO backup plan.',
                ],
                'result' => [
                    'fr' => "En phase de recette, avec un dossier d'acceptation de 29 cas. La validation juridique de la signature de l'autorité reste à trancher avant la mise en production.",
                    'en' => 'In acceptance testing, with a 29-case acceptance file. Legal validation of the authority signature is still to be settled before go-live.',
                ],
            ],
            [
                'slug' => 'gesmar-verif',
                'title' => ['fr' => 'GesmarVerif', 'en' => 'GesmarVerif'],
                'accent' => '#0b6bb5',
                'domains' => ['dev', 'securite'],
                'jobs' => ['full-stack'],
                'tech' => ['Expo', 'TypeScript'],
                'context' => [
                    'fr' => "L'application mobile compagnon de GESMAR : les agents de contrôle scannent le QR code d'un certificat pour en vérifier l'authenticité, et signalent un document suspect.",
                    'en' => "The mobile companion to GESMAR: inspection officers scan a certificate's QR code to check it is genuine, and report a suspicious document.",
                ],
                'realization' => [
                    'fr' => "Application React Native (Expo) avec double verrou (code PIN haché et biométrie), liaison unique entre une clé d'usage et un appareil, activation par QR de provisioning, signalements géolocalisés avec photo comme preuve. Activation, révocation et réinitialisation des appareils côté administration.",
                    'en' => 'React Native (Expo) app with a double lock (hashed PIN and biometrics), a one-to-one binding between an access key and a device, activation through a provisioning QR code, and geolocated reports with a photo as evidence. Device activation, revocation and reset are handled from the admin side.',
                ],
                'result' => [
                    'fr' => 'Version 1 quasi complète, prête pour la recette interne avec le backend GESMAR.',
                    'en' => 'Version 1 nearly complete, ready for internal acceptance testing with the GESMAR backend.',
                ],
            ],
            [
                'slug' => 'portalfy',
                'title' => ['fr' => 'PortalFy', 'en' => 'PortalFy'],
                'featured' => true,
                'accent' => '#e0714f',
                'domains' => ['dev', 'infra'],
                'jobs' => ['full-stack', 'charge-it'],
                'tech' => [...$laravel, 'TailwindCSS'],
                'context' => [
                    'fr' => "Un portail captif WiFi payant : le client choisit un forfait, règle par mobile money et reçoit son code d'accès en quelques secondes, sans créer de compte.",
                    'en' => 'A paid WiFi captive portal: customers pick a plan, pay by mobile money and get their access code within seconds, without creating an account.',
                ],
                'realization' => [
                    'fr' => 'Création automatique des accès sur un routeur MikroTik (Hotspot), paiement Wave, Orange Money, MTN et carte derrière une interface indépendante du fournisseur, webhook signé (HMAC) avec protection anti-rejeu, affichage temps réel du code, factures PDF, analyse statique et intégration continue.',
                    'en' => 'Automatic access creation on a MikroTik router (Hotspot), Wave, Orange Money, MTN and card payments behind a provider-independent interface, a signed webhook (HMAC) with replay protection, real-time code display, PDF invoices, static analysis and continuous integration.',
                ],
                'result' => [
                    'fr' => 'En développement : structure, parcours public et tests en place, intégrations paiement et routeur en cours de finalisation.',
                    'en' => 'In development: structure, public flow and tests are in place; payment and router integrations are being finalised.',
                ],
            ],
            [
                'slug' => 'myicpe',
                'title' => ['fr' => 'MyICPE', 'en' => 'MyICPE'],
                'accent' => '#2f8f6b',
                'domains' => ['dev'],
                'jobs' => ['full-stack'],
                'tech' => ['Expo', 'TypeScript'],
                'context' => [
                    'fr' => "Une application mobile de terrain pour les agents chargés du suivi des installations classées pour la protection de l'environnement, au sein d'un organisme public de lutte contre la pollution.",
                    'en' => 'A field mobile app for the agents monitoring installations classified for environmental protection, at a public anti-pollution body.',
                ],
                'realization' => [
                    'fr' => 'Environ 45 écrans : fiches entreprises, inspections, cartographie, fonctionnement hors connexion avec synchronisation, verrouillage par code PIN et biométrie. Vérification de licence et mises à jour intégrées, avec un site vitrine dédié.',
                    'en' => 'Around 45 screens: company records, inspections, mapping, offline operation with synchronisation, PIN and biometric lock. Built-in licence check and updates, with a dedicated showcase website.',
                ],
                'result' => [
                    'fr' => 'Utilisée sur le terrain par les agents, en complément de la plateforme de gestion des installations classées.',
                    'en' => 'Used in the field by agents, alongside the classified-installations management platform.',
                ],
            ],
            [
                'slug' => 'pollumar',
                'title' => ['fr' => 'Pollumar', 'en' => 'Pollumar'],
                'accent' => '#c79a1f',
                'domains' => ['dev'],
                'jobs' => ['full-stack'],
                'tech' => ['PHP', 'Laravel', 'Pest', 'Redis'],
                'context' => [
                    'fr' => 'Une plateforme de suivi de formation pour une organisation structurée en unités : exercices, quiz, évaluations et présence.',
                    'en' => 'A training-tracking platform for an organisation split into units: exercises, quizzes, assessments and attendance.',
                ],
                'realization' => [
                    'fr' => 'Assistant conversationnel intégré pour les exercices, avec historique des échanges et suivi de la consommation, évaluations à partir de modèles réutilisables, QR codes, exports Excel et PDF, accès publics par jeton sécurisé et limitation de débit.',
                    'en' => 'Built-in conversational assistant for exercises, with conversation history and usage tracking, assessments from reusable templates, QR codes, Excel and PDF exports, public access through secure tokens and rate limiting.',
                ],
                'result' => [
                    'fr' => 'En cours de développement actif, avec 31 fichiers de tests.',
                    'en' => 'Under active development, with 31 test files.',
                ],
            ],
            [
                'slug' => 'corptrix',
                'title' => ['fr' => 'Corptrix ERP', 'en' => 'Corptrix ERP'],
                'accent' => '#5b6ee1',
                'domains' => ['dev', 'securite'],
                'jobs' => ['full-stack', 'lead-tech'],
                'tech' => [...$laravel, 'Redis', 'Fortify (2FA)'],
                'context' => [
                    'fr' => "Un socle d'ERP modulaire : structure organisationnelle hiérarchique, postes, demandes d'inscription suivies par jeton, et boutique de modules activables par licence.",
                    'en' => 'A modular ERP foundation: hierarchical organisation structure, positions, token-tracked sign-up requests, and a store of licence-activated modules.',
                ],
                'realization' => [
                    'fr' => "Architecture en modules, double authentification, chiffrement des données sensibles, journal d'audit et sauvegardes automatisées. Le système de licence vérifie périodiquement auprès d'un service tiers et continue de fonctionner si celui-ci est indisponible.",
                    'en' => 'Modular architecture, two-factor authentication, encryption of sensitive data, an audit log and automated backups. The licence system checks periodically with a third-party service and keeps working if it is unavailable.',
                ],
                'result' => [
                    'fr' => 'Produit en construction, avec intégration continue sur PHP 8.4 et 8.5 et 21 fichiers de tests.',
                    'en' => 'Product under construction, with continuous integration on PHP 8.4 and 8.5 and 21 test files.',
                ],
            ],
            [
                'slug' => 'regest',
                'title' => ['fr' => 'Regest', 'en' => 'Regest'],
                'accent' => '#d9603f',
                'domains' => ['infra', 'dev'],
                'jobs' => ['charge-it'],
                'tech' => ['PHP', 'Laravel', 'MySQL'],
                'context' => [
                    'fr' => 'Un outil personnel de gestion de parc informatique, pensé pour une organisation répartie en directions, services et localités.',
                    'en' => 'A personal IT-asset management tool, designed for an organisation split into departments, services and locations.',
                ],
                'realization' => [
                    'fr' => "Inventaire des machines (postes, portables, serveurs), suivi des licences logicielles et des sièges disponibles, affectation aux agents, tickets d'intervention avec types d'actions, statuts et clôture, reprise des données de l'ancien outil.",
                    'en' => 'Inventory of machines (desktops, laptops, servers), software licences and available seats, assignment to staff, intervention tickets with action types, statuses and closure, and data migrated from the previous tool.',
                ],
                'result' => [
                    'fr' => 'Un outil de suivi unique pour le parc, les licences et les interventions.',
                    'en' => 'A single tool to follow the fleet, licences and interventions.',
                ],
            ],
            [
                'slug' => 'laraprint',
                'open_source' => true,
                'title' => ['fr' => 'Laraprint', 'en' => 'Laraprint'],
                'accent' => '#f0a020',
                'domains' => ['dev'],
                'jobs' => ['full-stack'],
                'tech' => ['PHP', 'Laravel', 'Pest'],
                'context' => [
                    'fr' => "Un paquet Laravel pour piloter n'importe quelle imprimante depuis une application : réseau, USB, Windows, CUPS, SMB ou fichier.",
                    'en' => 'A Laravel package to drive any printer from an application: network, USB, Windows, CUPS, SMB or file.',
                ],
                'realization' => [
                    'fr' => 'Reçus thermiques ESC/POS, étiquettes ZPL (Zebra), documents PDF, Word et Excel via le spouleur du système, et découverte automatique des imprimantes du réseau.',
                    'en' => 'ESC/POS thermal receipts, ZPL (Zebra) labels, PDF, Word and Excel documents through the system spooler, and automatic discovery of network printers.',
                ],
                'result' => [
                    'fr' => "Publié en paquet réutilisable, utilisé notamment par GESMAR pour l'impression sécurisée des certificats.",
                    'en' => 'Published as a reusable package, used in particular by GESMAR for secure certificate printing.',
                ],
            ],
            [
                'slug' => 'aps-connect',
                'open_source' => true,
                'title' => ['fr' => 'APS Connect', 'en' => 'APS Connect'],
                'accent' => '#3aa0d8',
                'domains' => ['dev', 'securite'],
                'jobs' => ['full-stack', 'lead-tech'],
                'tech' => ['PHP', 'Laravel', 'Pest'],
                'context' => [
                    'fr' => "Le client Laravel prêt à l'emploi pour les éditeurs : il vérifie les licences auprès de Registra et met à jour le logiciel automatiquement via App Station.",
                    'en' => 'The ready-to-use Laravel client for publishers: it checks licences with Registra and updates the software automatically through App Station.',
                ],
                'realization' => [
                    'fr' => "Paquet sans état, installable dans n'importe quelle application Laravel, qui sert de brique commune aux logiciels de l'écosystème (Corptrix en fait partie).",
                    'en' => "A stateless package, installable in any Laravel application, serving as the common building block for the ecosystem's software (Corptrix uses it).",
                ],
                'result' => [
                    'fr' => 'Une intégration de licence et de mise à jour en quelques lignes, au lieu de la réécrire dans chaque logiciel.',
                    'en' => 'Licence and update integration in a few lines, instead of rewriting it in every product.',
                ],
            ],
            [
                'slug' => 'aps-cli',
                'open_source' => true,
                'title' => ['fr' => 'APS CLI', 'en' => 'APS CLI'],
                'accent' => '#2b7f5f',
                'domains' => ['dev'],
                'jobs' => ['full-stack'],
                'tech' => ['Node.js', 'TypeScript'],
                'context' => [
                    'fr' => "Un outil en ligne de commande pour les éditeurs : lier son dépôt à un compte App Station, récupérer sa clé de licence et publier des versions sans passer par l'interface web.",
                    'en' => 'A command-line tool for publishers: link a repository to an App Station account, fetch the licence key and publish releases without going through the web interface.',
                ],
                'realization' => [
                    'fr' => "Publié sur npm (@app-station/cli), il signe cryptographiquement le dépôt comme preuve d'authenticité et automatise la publication des releases.",
                    'en' => 'Published on npm (@app-station/cli), it cryptographically signs the repository as proof of authenticity and automates release publishing.',
                ],
                'result' => [
                    'fr' => 'Le parcours éditeur tient dans le terminal, de la liaison à la publication.',
                    'en' => 'The publisher workflow fits in the terminal, from linking to publishing.',
                ],
            ],
            [
                'slug' => 'laravel-app-pairing',
                'open_source' => true,
                'title' => ['fr' => 'Laravel App Pairing', 'en' => 'Laravel App Pairing'],
                'accent' => '#8b5cc7',
                'domains' => ['dev', 'securite'],
                'jobs' => ['full-stack', 'lead-tech'],
                'tech' => ['PHP', 'Laravel', 'Pest'],
                'context' => [
                    'fr' => "Un paquet Laravel qui appaire deux applications et les fait s'authentifier mutuellement, par signatures Ed25519 plutôt que par secrets partagés statiques.",
                    'en' => 'A Laravel package that pairs two applications and authenticates them to each other, using Ed25519 signatures instead of static shared secrets.',
                ],
                'realization' => [
                    'fr' => 'Couche générique et réutilisable de requêtes signées entre applications, utilisée pour relier App Station et Registra.',
                    'en' => 'A generic, reusable layer of signed requests between applications, used to link App Station and Registra.',
                ],
                'result' => [
                    'fr' => 'Une communication inter-applications sans secret partagé à faire tourner.',
                    'en' => 'Inter-application communication with no shared secret to rotate.',
                ],
            ],
            [
                'slug' => 'infrastructure-reseau',
                'title' => ['fr' => 'Infrastructure et réseau', 'en' => 'Infrastructure and networking'],
                'accent' => '#14b8a6',
                'domains' => ['infra', 'securite'],
                'jobs' => ['charge-it', 'lead-tech'],
                'tech' => ['Proxmox', 'Docker', 'Nginx', 'MikroTik', 'WireGuard', 'Tailscale', 'Ollama'],
                'context' => [
                    'fr' => 'Mon infrastructure personnelle : un laboratoire de réseau et de serveurs où je teste ce que je déploie ensuite au travail.',
                    'en' => 'My personal infrastructure: a networking and server lab where I test what I later deploy at work.',
                ],
                'realization' => [
                    'fr' => "DNS, DHCP et reverse proxy HTTPS pour plus d'une dizaine de services, avec un certificat wildcard. Accès distant sécurisé par VPN, Wi-Fi via un routeur dédié, et un hotspot en cours de développement. Machines virtuelles sous Proxmox, services en conteneurs, sauvegardes et snapshots.",
                    'en' => 'DNS, DHCP and an HTTPS reverse proxy for more than a dozen services, with a wildcard certificate. Remote access secured by VPN, Wi-Fi through a dedicated router, and a hotspot under development. Virtual machines on Proxmox, services in containers, backups and snapshots.',
                ],
                'result' => [
                    'fr' => 'Un terrain fiable pour expérimenter, avec un accès distant sécurisé et des services exposés proprement.',
                    'en' => 'A reliable ground for experimenting, with secure remote access and cleanly exposed services.',
                ],
            ],
            [
                'slug' => 'fne-client',
                'open_source' => true,
                'title' => ['fr' => 'fne-client', 'en' => 'fne-client'],
                'accent' => '#e0714f',
                'domains' => ['dev'],
                'jobs' => ['full-stack'],
                'tech' => ['PHP', 'Laravel', 'Pest'],
                'context' => [
                    'fr' => "Une bibliothèque pour les éditeurs de logiciels de facturation et d'ERP qui doivent faire certifier leurs factures électroniques normalisées auprès de la DGI de Côte d'Ivoire.",
                    'en' => 'A library for billing and ERP software publishers who must get their standardised electronic invoices certified with the Ivorian tax authority (DGI).',
                ],
                'realization' => [
                    'fr' => 'Journal de suivi des certifications, messages d\'erreur structurés en trois langues et contrôles automatisés de la qualité du code. Publiée sur Packagist.',
                    'en' => 'Certification tracking log, structured error messages in three languages and automated code-quality checks. Published on Packagist.',
                ],
                'result' => [
                    'fr' => 'Publiée en open source, en version alpha.',
                    'en' => 'Published as open source, in alpha.',
                ],
            ],
        ];
    }
}
