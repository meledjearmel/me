<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Skill;
use App\Models\Technology;
use Illuminate\Database\Seeder;

/**
 * Compétences regroupées par domaine, d'après le CV et les fiches projets.
 * Chaque compétence a une description courte (carte), un texte détaillé
 * (fenêtre au clic) et les technologies dont les logos s'affichent.
 */
class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->skills() as $domainKey => $skills) {
            $domain = Domain::query()->where('key', $domainKey)->firstOrFail();

            foreach ($skills as $index => $data) {
                $skill = Skill::query()->create([
                    'domain_id' => $domain->id,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'details' => $data['details'],
                    'sort_order' => $index + 1,
                ]);

                $skill->technologies()->sync(
                    Technology::query()
                        ->whereIn('name', $data['tech'])
                        ->get()
                        ->sortBy(fn (Technology $technology): int => (int) array_search($technology->name, $data['tech'], true))
                        ->values()
                        ->mapWithKeys(fn (Technology $technology, int $position): array => [$technology->id => ['sort_order' => $position]])
                        ->all(),
                );
            }
        }
    }

    /**
     * @return array<string, list<array{name: array<string, string>, description: array<string, string>, details: array<string, string>, tech: list<string>}>>
     */
    private function skills(): array
    {
        return [
            'dev' => [
                [
                    'name' => ['fr' => 'Laravel et PHP', 'en' => 'Laravel and PHP'],
                    'description' => [
                        'fr' => "Applications métier complètes : API versionnées, files d'attente, tâches planifiées, temps réel.",
                        'en' => 'Full business applications: versioned APIs, queues, scheduled jobs, real time.',
                    ],
                    'details' => [
                        'fr' => "C'est ma technologie principale. Je conçois des applications complètes : modèles et migrations, API REST versionnées, files d'attente, tâches planifiées, WebSockets avec Reverb, authentification avec Fortify et Sanctum, et autorisations par policies.\n\nJ'ai construit sur cette base une place de marché de logiciels (24 modèles, 75 composants Livewire) et un service de vérification de licences, tous deux en production, ainsi qu'un ERP modulaire et une plateforme de suivi de formation.",
                        'en' => "This is my main technology. I design complete applications: models and migrations, versioned REST APIs, queues, scheduled jobs, WebSockets with Reverb, authentication with Fortify and Sanctum, and authorisation through policies.\n\nOn this foundation I built a software marketplace (24 models, 75 Livewire components) and a licence-verification service, both in production, plus a modular ERP and a training-tracking platform.",
                    ],
                    'tech' => ['PHP', 'Laravel', 'Redis'],
                ],
                [
                    'name' => ['fr' => 'Livewire, Inertia et React', 'en' => 'Livewire, Inertia and React'],
                    'description' => [
                        'fr' => 'Interfaces réactives, avec ou sans SPA, en TypeScript et Tailwind CSS.',
                        'en' => 'Reactive interfaces, with or without an SPA, in TypeScript and Tailwind CSS.',
                    ],
                    'details' => [
                        'fr' => "Je choisis l'outil selon le besoin : Livewire pour des interfaces riches sans écrire d'API dédiée (marketplace, boutique, portail WiFi), Inertia avec React et TypeScript quand une vraie application monopage apporte un vrai confort, comme pour ce portfolio.\n\nJ'ai aussi utilisé Vue avec Laravel pour une application de gestion de fiches produits WordPress, et j'ai contribué à un projet Angular.\n\nCôté style, Tailwind CSS 4, Alpine.js pour les petites interactions et Vite pour la compilation.",
                        'en' => "I pick the tool for the job: Livewire for rich interfaces without a dedicated API (marketplace, shop, WiFi portal), Inertia with React and TypeScript when a true single-page app brings real comfort, as with this portfolio.\n\nI have also used Vue with Laravel for a WordPress product-sheet management application, and I have contributed to an Angular project.\n\nFor styling, Tailwind CSS 4, Alpine.js for small interactions and Vite for the build.",
                    ],
                    'tech' => ['Livewire', 'Inertia', 'React', 'Vue.js', 'Angular', 'TypeScript', 'TailwindCSS', 'Alpine.js', 'Vite'],
                ],
                [
                    'name' => ['fr' => 'Applications mobiles', 'en' => 'Mobile apps'],
                    'description' => [
                        'fr' => 'React Native (Expo) et Flutter : hors connexion, biométrie, caméra, géolocalisation.',
                        'en' => 'React Native (Expo) and Flutter: offline mode, biometrics, camera, geolocation.',
                    ],
                    'details' => [
                        'fr' => "Je conçois des applications mobiles de terrain avec React Native et Expo. MyICPE, utilisée par des agents chargés du suivi d'installations classées, compte environ 45 écrans et fonctionne hors connexion avec synchronisation.\n\nGesmarVerif, elle, scanne des QR codes pour vérifier des certificats, avec verrouillage par code PIN et biométrie, signalements géolocalisés et photo comme preuve.\n\nJ'utilise aussi Flutter pour le mobile multiplateforme, et je connais NativePHP pour créer des applications natives avec PHP.",
                        'en' => "I build field mobile apps with React Native and Expo. MyICPE, used by agents monitoring classified installations, has around 45 screens and works offline with synchronisation.\n\nGesmarVerif scans QR codes to verify certificates, with PIN and biometric lock, geolocated reports and a photo as evidence.\n\nI also use Flutter for cross-platform mobile, and I know NativePHP for building native apps with PHP.",
                    ],
                    'tech' => ['React Native', 'Expo', 'Flutter', 'TypeScript', 'NativePHP'],
                ],
                [
                    'name' => ['fr' => 'Tests et qualité', 'en' => 'Testing and quality'],
                    'description' => [
                        'fr' => 'Pest, analyse statique, Pint et intégration continue.',
                        'en' => 'Pest, static analysis, Pint and continuous integration.',
                    ],
                    'details' => [
                        'fr' => "Un logiciel qu'on ne peut pas modifier sans peur est un logiciel en danger. J'écris mes tests avec Pest, construit sur PHPUnit (plus de 630 sur la marketplace, 126 fichiers sur le service de licences), j'utilise l'analyse statique avec PHPStan et Larastan, et Pint pour le style.\n\nL'intégration continue (GitHub Actions) lance les tests sur plusieurs versions de PHP à chaque envoi de code.",
                        'en' => "Software you cannot change without fear is software at risk. I write my tests with Pest, built on PHPUnit (over 630 on the marketplace, 126 files on the licence service), use static analysis with PHPStan and Larastan, and Pint for style.\n\nContinuous integration (GitHub Actions) runs the tests on several PHP versions on every push.",
                    ],
                    'tech' => ['Pest', 'PHPUnit', 'PHPStan', 'GitHub'],
                ],
                [
                    'name' => ['fr' => 'Paquets et outils', 'en' => 'Packages and tooling'],
                    'description' => [
                        'fr' => 'Paquets Laravel réutilisables et outils en ligne de commande (npm).',
                        'en' => 'Reusable Laravel packages and command-line tools (npm).',
                    ],
                    'details' => [
                        'fr' => "Quand une brique sert à plusieurs projets, j'en fais un paquet. J'ai publié un SDK d'impression (imprimantes réseau, USB, thermiques et étiquettes), un client de licence et de mise à jour, et une couche d'authentification entre applications par signatures Ed25519.\n\nJ'ai aussi écrit un outil en ligne de commande publié sur npm, qui lie un dépôt à un compte, le signe et publie les versions.\n\nMon code est versionné avec Git et hébergé sur GitLab et GitHub, avec revue des changements et intégration continue.",
                        'en' => "When a building block serves several projects, I turn it into a package. I published a printing SDK (network, USB, thermal and label printers), a licence and update client, and an authentication layer between applications using Ed25519 signatures.\n\nI also wrote a command-line tool published on npm, which links a repository to an account, signs it and publishes releases.\n\nMy code is versioned with Git and hosted on GitLab and GitHub, with change review and continuous integration.",
                    ],
                    'tech' => ['Git', 'GitHub', 'GitLab', 'PHP', 'Laravel', 'Node.js', 'TypeScript', 'PhpStorm', 'Cursor'],
                ],
                [
                    'name' => ['fr' => 'Sites WordPress et CMS', 'en' => 'WordPress and CMS sites'],
                    'description' => [
                        'fr' => 'Sites vitrines et boutiques en ligne pour des clients, avec WordPress.',
                        'en' => 'Showcase sites and online shops for clients, built with WordPress.',
                    ],
                    'details' => [
                        'fr' => "En freelance, j'ai réalisé environ onze sites pour des clients, dont huit boutiques en ligne, surtout en intégration WordPress (l'une d'elles en ReactJS), ainsi que des sites vitrines. Je m'appuie sur des constructeurs comme Elementor et Divi quand ils accélèrent le travail, et je code sur mesure quand le besoin dépasse ce qu'ils permettent.\n\nJe m'occupe de la structure du site, de l'intégration, du parcours d'achat pour les boutiques et de la prise en main par le client.",
                        'en' => "As a freelancer, I built about eleven sites for clients, including eight online shops, mostly as WordPress integrations (one of them in ReactJS), as well as showcase sites. I rely on builders such as Elementor and Divi when they speed things up, and write custom code when the need goes beyond what they allow.\n\nI handle the site structure, the integration, the purchase flow for shops and the handover to the client.",
                    ],
                    'tech' => ['WordPress', 'Elementor', 'Divi', 'PHP'],
                ],
                [
                    'name' => ['fr' => "L'IA dans les applications", 'en' => 'AI in applications'],
                    'description' => [
                        'fr' => 'Assistants conversationnels, suivi de consommation et modèles locaux.',
                        'en' => 'Conversational assistants, usage tracking and local models.',
                    ],
                    'details' => [
                        'fr' => "J'intègre l'IA quand elle sert un usage précis. Sur une plateforme de suivi de formation, un assistant conversationnel accompagne les exercices, avec l'historique des échanges et le suivi de la consommation de jetons.\n\nJe teste aussi des modèles locaux avec Ollama sur mon laboratoire personnel, et je m'appuie sur les grands modèles du marché pour les fonctions qui le demandent.\n\nAu quotidien, j'utilise aussi des outils d'IA comme Claude Code et Cursor pour m'aider à développer.",
                        'en' => "I bring AI in when it serves a precise purpose. On a training-tracking platform, a conversational assistant guides the exercises, with conversation history and token-usage tracking.\n\nI also test local models with Ollama on my personal lab, and rely on the major models on the market for features that call for them.\n\nDay to day, I also use AI tools such as Claude Code and Cursor to help me develop.",
                    ],
                    'tech' => ['Anthropic AI', 'OpenAI', 'Alphabet', 'Ollama', 'Cursor'],
                ],
            ],
            'infra' => [
                [
                    'name' => ['fr' => 'Administration Linux', 'en' => 'Linux administration'],
                    'description' => [
                        'fr' => "Serveurs Linux et environnements d'un parc d'environ 200 utilisateurs, supervisés.",
                        'en' => 'Linux servers and the environments of a fleet of about 200 users, monitored.',
                    ],
                    'details' => [
                        'fr' => "Au CIAPOL, je configure et je maintiens les serveurs Linux (sous Proxmox) qui hébergent les services internes pour environ 200 utilisateurs, avec une supervision par Zabbix. J'administre aussi les environnements Linux du parc : installation, configuration, dépannage.\n\nLes mises à jour des systèmes et des applications sont planifiées, pour que rien ne reste en retard sans qu'on le sache.",
                        'en' => "At CIAPOL, I configure and maintain the Linux servers (on Proxmox) hosting the internal services for about 200 users, monitored with Zabbix. I also administer the fleet's Linux environments: installation, configuration, troubleshooting.\n\nSystem and application updates are scheduled, so nothing stays behind without anyone knowing.",
                    ],
                    'tech' => ['Linux', 'Ubuntu', 'Debian', 'Zabbix'],
                ],
                [
                    'name' => ['fr' => 'Supervision et parc informatique', 'en' => 'Monitoring and IT asset management'],
                    'description' => [
                        'fr' => "Zabbix, GLPI : supervision, inventaire, licences et demandes d'intervention.",
                        'en' => 'Zabbix, GLPI: monitoring, inventory, licences and intervention requests.',
                    ],
                    'details' => [
                        'fr' => "J'ai déployé et j'administre GLPI pour suivre les équipements, les logiciels, les licences et les demandes d'intervention. La supervision des serveurs passe par Zabbix.\n\nJ'ai aussi conçu Regest, un outil personnel de gestion de parc : inventaire des machines (ancienneté, affectation), licences avec calcul des sièges restants, et tickets d'intervention avec statut et historique.",
                        'en' => "I deployed and run GLPI to track equipment, software, licences and intervention requests. Server monitoring goes through Zabbix.\n\nI also designed Regest, a personal IT-asset tool: machine inventory (age, assignment), licences with remaining-seat calculation, and intervention tickets with status and history.",
                    ],
                    'tech' => ['Zabbix', 'GLPI'],
                ],
                [
                    'name' => ['fr' => 'Support et accompagnement des utilisateurs', 'en' => 'User support and training'],
                    'description' => [
                        'fr' => 'Incidents, comptes et droits, formation des agents, rapports.',
                        'en' => 'Incidents, accounts and permissions, staff training, reports.',
                    ],
                    'details' => [
                        'fr' => "Au quotidien, je traite les incidents signalés par les utilisateurs, j'assiste les agents et j'installe les logiciels dont ils ont besoin. Je gère les comptes, les accès et les droits d'administration attribués aux personnes habilitées.\n\nJ'ai participé à des tournées de formation des agents aux bonnes pratiques informatiques, et je prépare les rapports et états de suivi des activités IT.",
                        'en' => "Day to day, I handle incidents reported by users, assist staff and install the software they need. I manage the accounts, access and administration rights granted to authorised people.\n\nI took part in training rounds teaching staff good IT practices, and I prepare reports and progress statements on IT activities.",
                    ],
                    'tech' => ['GLPI'],
                ],
                [
                    'name' => ['fr' => 'Serveurs et déploiement', 'en' => 'Servers and deployment'],
                    'description' => [
                        'fr' => 'Configuration de serveurs, déploiement continu, PM2, Nginx.',
                        'en' => 'Server configuration, continuous deployment, PM2, Nginx.',
                    ],
                    'details' => [
                        'fr' => "Je déploie mes applications moi-même. Sur le service de licences, un pipeline GitLab CI se connecte en SSH au serveur de production, installe les dépendances, compile le front, lance les migrations et recharge la configuration à chaque livraison.\n\nLes files d'attente et le planificateur tournent sous PM2, les WebSockets sous systemd, derrière Nginx.\n\nEn freelance, j'assurais aussi l'hébergement et les serveurs des sites livrés.",
                        'en' => "I deploy my applications myself. On the licence service, a GitLab CI pipeline connects to the production server over SSH, installs dependencies, builds the front end, runs migrations and refreshes configuration on every release.\n\nQueues and the scheduler run under PM2, WebSockets under systemd, behind Nginx.\n\nAs a freelancer, I also handled the hosting and servers of the sites I delivered.",
                    ],
                    'tech' => ['Nginx', 'Apache', 'Docker', 'GitLab', 'Node.js'],
                ],
                [
                    'name' => ['fr' => 'Virtualisation, conteneurs et sauvegardes', 'en' => 'Virtualisation, containers and backups'],
                    'description' => [
                        'fr' => 'Proxmox, Docker, snapshots et sauvegardes locales et distantes.',
                        'en' => 'Proxmox, Docker, snapshots and local and remote backups.',
                    ],
                    'details' => [
                        'fr' => "Je fais tourner mes services sur Proxmox et Docker. Au CIAPOL, j'ai participé à la mise en œuvre et à la restauration de sauvegardes et de snapshots réguliers, en local et sur un serveur distant.\n\nSur mon laboratoire personnel, j'expose les services avec Nginx et je teste des modèles d'IA locaux avec Ollama.",
                        'en' => "I run my services on Proxmox and Docker. At CIAPOL, I took part in setting up and restoring regular backups and snapshots, locally and on a remote server.\n\nOn my personal lab, I expose services with Nginx and test local AI models with Ollama.",
                    ],
                    'tech' => ['Proxmox', 'Docker', 'Nginx', 'Ollama'],
                ],
                [
                    'name' => ['fr' => 'Réseau et accès distant', 'en' => 'Networking and remote access'],
                    'description' => [
                        'fr' => 'DNS, DHCP, reverse proxy HTTPS, VPN et routeur dédié.',
                        'en' => 'DNS, DHCP, HTTPS reverse proxy, VPN and a dedicated router.',
                    ],
                    'details' => [
                        'fr' => "Sur mon infrastructure personnelle, j'assure le DNS, le DHCP et un reverse proxy HTTPS pour plus d'une dizaine de services, avec un certificat wildcard.\n\nL'accès distant est sécurisé par VPN (WireGuard, Tailscale) et le réseau Wi-Fi passe par un routeur dédié MikroTik. Un hotspot est en cours de développement : le client paie et son accès est créé automatiquement sur le routeur.",
                        'en' => "On my personal infrastructure, I run DNS, DHCP and an HTTPS reverse proxy for more than a dozen services, with a wildcard certificate.\n\nRemote access is secured through VPN (WireGuard, Tailscale) and the Wi-Fi network goes through a dedicated MikroTik router. A hotspot is under development: the customer pays and their access is created automatically on the router.",
                    ],
                    'tech' => ['MikroTik', 'WireGuard', 'Tailscale', 'Nginx'],
                ],
                [
                    'name' => ['fr' => 'Bases de données', 'en' => 'Databases'],
                    'description' => [
                        'fr' => 'MySQL, MariaDB, PostgreSQL, SQLite et Redis : modélisation, exploitation, cache.',
                        'en' => 'MySQL, MariaDB, PostgreSQL, SQLite and Redis: modelling, operation, caching.',
                    ],
                    'details' => [
                        'fr' => "Je modélise et j'exploite MySQL (et MariaDB), PostgreSQL et SQLite : schémas, migrations, index, transactions et reprise de données depuis d'anciens outils.\n\nJ'utilise Redis pour le cache, les sessions et les files d'attente.",
                        'en' => "I model and run MySQL (and MariaDB), PostgreSQL and SQLite: schemas, migrations, indexes, transactions and data migration from legacy tools.\n\nI use Redis for cache, sessions and queues.",
                    ],
                    'tech' => ['MySQL', 'MariaDB', 'PostgreSQL', 'SQLite', 'Redis'],
                ],
            ],
            'securite' => [
                [
                    'name' => ['fr' => 'Authentification forte', 'en' => 'Strong authentication'],
                    'description' => [
                        'fr' => 'Double authentification, passkeys, code PIN haché et biométrie.',
                        'en' => 'Two-factor authentication, passkeys, hashed PIN and biometrics.',
                    ],
                    'details' => [
                        'fr' => "Sur la marketplace et le service de licences, j'ai mis en place la double authentification par e-mail et les passkeys (WebAuthn) pour les administrateurs comme pour les clients.\n\nSur mobile, l'accès est protégé par un code PIN haché avec sel et par la biométrie.",
                        'en' => "On the marketplace and the licence service, I set up email two-factor authentication and passkeys (WebAuthn) for both administrators and customers.\n\nOn mobile, access is protected by a salted, hashed PIN and by biometrics.",
                    ],
                    'tech' => ['Fortify (2FA)', 'Laravel', 'Expo'],
                ],
                [
                    'name' => ['fr' => 'Droits et habilitations', 'en' => 'Roles and permissions'],
                    'description' => [
                        'fr' => "Rôles, permissions déléguées, politiques d'accès par ressource.",
                        'en' => 'Roles, delegated permissions, per-resource access policies.',
                    ],
                    'details' => [
                        'fr' => "Je modélise les accès au plus près du métier : trois rôles et onze permissions déléguées à l'intérieur d'une équipe d'éditeurs, neuf profils métier pour un système de certificats, onze policies pour contrôler chaque ressource.\n\nL'objectif : que chacun voie et fasse ce qu'il doit, et rien de plus.",
                        'en' => "I model access as close to the business as possible: three roles and eleven permissions delegated inside a publisher team, nine business roles for a certificate system, eleven policies controlling each resource.\n\nThe goal: everyone sees and does what they should, and nothing more.",
                    ],
                    'tech' => ['Laravel', 'PHP'],
                ],
                [
                    'name' => ['fr' => 'Sécurité des API', 'en' => 'API security'],
                    'description' => [
                        'fr' => 'Rotation de clés, signatures Ed25519, webhooks signés, limitation de débit.',
                        'en' => 'Key rotation, Ed25519 signatures, signed webhooks, rate limiting.',
                    ],
                    'details' => [
                        'fr' => "Les clés d'API se renouvellent sans coupure grâce à une clé actuelle et une clé précédente acceptées ensemble. Deux applications s'authentifient l'une l'autre avec des signatures Ed25519 plutôt qu'un secret partagé.\n\nLes webhooks de paiement sont vérifiés par signature HMAC avec protection contre le rejeu, et les points d'entrée sensibles ont une limitation de débit.",
                        'en' => "API keys rotate without downtime, with a current and a previous key accepted together. Two applications authenticate each other with Ed25519 signatures rather than a shared secret.\n\nPayment webhooks are verified with an HMAC signature and replay protection, and sensitive entry points are rate limited.",
                    ],
                    'tech' => ['Laravel', 'Redis'],
                ],
                [
                    'name' => ['fr' => 'Traçabilité', 'en' => 'Traceability'],
                    'description' => [
                        'fr' => "Journaux d'audit, historique des statuts, plans de sauvegarde (RPO/RTO).",
                        'en' => 'Audit logs, status history, backup plans (RPO/RTO).',
                    ],
                    'details' => [
                        'fr' => "Pour un système de certificats officiels, j'ai mis en place un journal d'audit interne et un registre de destruction à double validation. Ailleurs, des tables d'événements gardent l'historique des changements de statut.\n\nJe définis aussi des plans de sauvegarde avec objectifs de reprise (RPO et RTO) et des sauvegardes automatisées.",
                        'en' => "For an official-certificate system, I set up an internal audit log and a destruction register with double validation. Elsewhere, event tables keep the history of status changes.\n\nI also define backup plans with recovery objectives (RPO and RTO) and automated backups.",
                    ],
                    'tech' => ['Laravel', 'PostgreSQL'],
                ],
                [
                    'name' => ['fr' => 'Sécurité des systèmes', 'en' => 'Systems security'],
                    'description' => [
                        'fr' => 'fail2ban, audits Lynis, mots de passe, sauvegardes et mises à jour.',
                        'en' => 'fail2ban, Lynis audits, passwords, backups and updates.',
                    ],
                    'details' => [
                        'fr' => "Je durcis les serveurs avec fail2ban et je contrôle leur niveau de sécurité avec des audits Lynis. Les mots de passe sont gérés de façon sécurisée, et l'authentification à deux facteurs protège mes applications.\n\nLes sauvegardes, les snapshots et les mises à jour planifiées complètent le dispositif.",
                        'en' => "I harden servers with fail2ban and check their security level with Lynis audits. Passwords are managed securely, and two-factor authentication protects my applications.\n\nBackups, snapshots and scheduled updates complete the picture.",
                    ],
                    'tech' => ['Linux', 'Debian', 'Ubuntu'],
                ],
            ],
            'management' => [
                [
                    'name' => ['fr' => 'Architecture et décisions techniques', 'en' => 'Architecture and technical decisions'],
                    'description' => [
                        'fr' => 'Choix de stack, conception de systèmes interdépendants, arbitrages.',
                        'en' => 'Stack choices, design of interdependent systems, trade-offs.',
                    ],
                    'details' => [
                        'fr' => "Je porte les décisions d'architecture et les choix techniques d'un ensemble de produits interdépendants : plateformes web, applications mobiles et composants réutilisables. J'arbitre entre plusieurs solutions en pesant le besoin, le coût de maintenance et la sécurité.\n\nApp Station, Registra et leurs paquets (laravel-app-pairing, aps-connect, aps-cli) forment ainsi un même socle que d'autres produits et d'autres éditeurs peuvent réutiliser.",
                        'en' => "I own the architecture decisions and technical choices of a set of interdependent products: web platforms, mobile apps and reusable components. I weigh solutions against need, maintenance cost and security.\n\nApp Station, Registra and their packages (laravel-app-pairing, aps-connect, aps-cli) thus form a single foundation that other products and publishers can reuse.",
                    ],
                    'tech' => ['Laravel', 'Livewire', 'Inertia', 'Expo'],
                ],
                [
                    'name' => ['fr' => 'Standards de qualité transverses', 'en' => 'Cross-product quality standards'],
                    'description' => [
                        'fr' => 'Conventions de tests, analyse statique et intégration continue sur plusieurs produits.',
                        'en' => 'Testing conventions, static analysis and continuous integration across products.',
                    ],
                    'details' => [
                        'fr' => "Je définis et j'applique les mêmes standards à travers mes produits : tests automatisés avec Pest, analyse statique avec PHPStan et Larastan, style avec Pint, et intégration continue sur plusieurs versions d'environnement.\n\nL'objectif : qu'un produit puisse évoluer sans peur, et qu'un autre développeur s'y retrouve vite.",
                        'en' => "I define and apply the same standards across my products: automated tests with Pest, static analysis with PHPStan and Larastan, style with Pint, and continuous integration on several environment versions.\n\nThe goal: a product can evolve without fear, and another developer finds their way quickly.",
                    ],
                    'tech' => ['Pest', 'PHPStan', 'GitHub', 'GitLab'],
                ],
                [
                    'name' => ['fr' => 'Automatisation et outillage', 'en' => 'Automation and tooling'],
                    'description' => [
                        'fr' => 'Des outils et des processus qui réduisent la charge opérationnelle.',
                        'en' => 'Tools and processes that reduce operational load.',
                    ],
                    'details' => [
                        'fr' => "J'automatise ce qui revient : revalidation périodique des licences, réconciliation des paiements, déploiement continu vers un serveur Linux, files d'attente et tâches planifiées.\n\nChaque automatisation retire une tâche manuelle à l'équipe et une source d'erreur au produit.",
                        'en' => "I automate what keeps coming back: periodic licence revalidation, payment reconciliation, continuous deployment to a Linux server, queues and scheduled jobs.\n\nEvery automation removes a manual task from the team and a source of error from the product.",
                    ],
                    'tech' => ['Laravel', 'Redis', 'GitLab'],
                ],
                [
                    'name' => ['fr' => 'Diffusion de bonnes pratiques', 'en' => 'Sharing good practices'],
                    'description' => [
                        'fr' => "Des composants open source réutilisés par d'autres développeurs.",
                        'en' => 'Open source components reused by other developers.',
                    ],
                    'details' => [
                        'fr' => "Je publie des composants open source conçus pour être fiables, documentés et faciles à intégrer : authentification mutuelle entre applications, client de licence, outil en ligne de commande, impression, certification de factures électroniques.\n\nC'est ma manière de diffuser des pratiques d'ingénierie au-delà de mon périmètre direct.",
                        'en' => "I publish open source components designed to be reliable, documented and easy to integrate: mutual authentication between applications, a licence client, a command-line tool, printing, electronic-invoice certification.\n\nIt is my way of spreading engineering practices beyond my direct scope.",
                    ],
                    'tech' => ['GitHub', 'PHP', 'Laravel', 'Node.js'],
                ],
                [
                    'name' => ['fr' => 'Cadrage et analyse du besoin', 'en' => 'Scoping and requirements analysis'],
                    'description' => [
                        'fr' => 'Traduire un besoin métier en solution concrète, avec les utilisateurs.',
                        'en' => 'Turning a business need into a concrete solution, with the users.',
                    ],
                    'details' => [
                        'fr' => "Je traduis des besoins métier (agents de terrain, administration publique, organisme interne) en solutions concrètes : recueil du besoin, cadrage des fonctionnalités, modélisation des processus, des états d'un dossier, des rôles et des niveaux d'accès.\n\nMyICPE a été cadrée avec les agents de terrain, GESMAR avec une administration publique, du circuit métier jusqu'à la vérification sur le terrain.",
                        'en' => "I turn business needs (field agents, public administration, an internal organisation) into concrete solutions: gathering requirements, scoping features, modelling processes, case states, roles and access levels.\n\nMyICPE was scoped with field agents, GESMAR with a public administration, from the business workflow to on-site verification.",
                    ],
                    'tech' => [],
                ],
                [
                    'name' => ['fr' => 'Pilotage de projets', 'en' => 'Project management'],
                    'description' => [
                        'fr' => "Plusieurs projets applicatifs et d'infrastructure menés en parallèle, du cadrage à la mise en production.",
                        'en' => 'Several application and infrastructure projects run in parallel, from scoping to go-live.',
                    ],
                    'details' => [
                        'fr' => "J'ai mené en parallèle des projets aux enjeux très différents : une application mobile de terrain, une solution pour une administration, une infrastructure interne pour environ 200 utilisateurs, une plateforme de suivi de formation. À chaque fois : planification, suivi d'avancement, gestion des risques, mise en production et suivi.\n\nJ'assure le lien entre le besoin métier, les contraintes techniques et les utilisateurs finaux.",
                        'en' => "I have run in parallel projects with very different stakes: a field mobile app, a solution for a public administration, an internal infrastructure for about 200 users, a training-tracking platform. Each time: planning, progress tracking, risk management, go-live and follow-up.\n\nI am the link between the business need, the technical constraints and the end users.",
                    ],
                    'tech' => ['GitLab', 'GitHub'],
                ],
                [
                    'name' => ['fr' => 'Encadrement et montée en compétences', 'en' => 'Mentoring and skills development'],
                    'description' => [
                        'fr' => 'Accompagner des collaborateurs en début de parcours, au CIAPOL et en freelance.',
                        'en' => 'Guiding team members early in their careers, at CIAPOL and as a freelancer.',
                    ],
                    'details' => [
                        'fr' => "J'ai encadré une douzaine de collaborateurs en début de parcours : environ neuf au CIAPOL et trois en freelance.\n\nL'objectif : que chacun progresse et gagne en autonomie, sans que la qualité de ce qui est livré en souffre.",
                        'en' => "I have guided about a dozen team members early in their careers: around nine at CIAPOL and three as a freelancer.\n\nThe goal: everyone grows and becomes more autonomous, without the quality of what is delivered suffering.",
                    ],
                    'tech' => ['Git', 'GitHub', 'GitLab'],
                ],
                [
                    'name' => ['fr' => 'Coordination des parties prenantes', 'en' => 'Stakeholder coordination'],
                    'description' => [
                        'fr' => 'Échanges avec utilisateurs, administrations et clients pour ajuster ce qui est livré.',
                        'en' => 'Working with users, administrations and clients to adjust what is delivered.',
                    ],
                    'details' => [
                        'fr' => "Je travaille avec les utilisateurs finaux, les administrations et les clients pour ajuster les solutions livrées. En freelance, j'ai cadré les besoins et livré une dizaine de sites à des clients aux attentes très différentes, de la prise de besoin à la mise en ligne.\n\nJ'accompagne aussi les utilisateurs : tournées de sensibilisation aux bonnes pratiques informatiques.",
                        'en' => "I work with end users, administrations and clients to adjust the delivered solutions. As a freelancer, I scoped needs and delivered about ten sites to clients with very different expectations, from requirements to launch.\n\nI also support users: awareness rounds on good IT practices.",
                    ],
                    'tech' => ['WordPress'],
                ],
                [
                    'name' => ['fr' => 'Gestion des risques et continuité', 'en' => 'Risk management and continuity'],
                    'description' => [
                        'fr' => 'Sécurité, sauvegardes, plans de reprise et continuité de service.',
                        'en' => 'Security, backups, recovery plans and service continuity.',
                    ],
                    'details' => [
                        'fr' => "Je prends en compte les risques dès le cadrage : authentification et contrôle des accès, plans de sauvegarde et de reprise (RPO et RTO), sauvegardes et snapshots réguliers en local et sur un serveur distant.\n\nLa continuité de service passe aussi par la documentation des interventions et l'historisation des changements.",
                        'en' => "I take risks into account from the scoping stage: authentication and access control, backup and recovery plans (RPO and RTO), regular backups and snapshots locally and on a remote server.\n\nService continuity also relies on documenting interventions and keeping a history of changes.",
                    ],
                    'tech' => ['Proxmox', 'Zabbix'],
                ],
                [
                    'name' => ['fr' => 'Suivi, reporting et documentation', 'en' => 'Tracking, reporting and documentation'],
                    'description' => [
                        'fr' => "Rapports d'activité, états de suivi, historique des interventions.",
                        'en' => 'Activity reports, progress statements, intervention history.',
                    ],
                    'details' => [
                        'fr' => "Je prépare des rapports et des états de suivi des activités informatiques, et je documente les interventions. Le suivi s'appuie sur des outils dédiés : tickets avec statut et historique, inventaire du parc et des licences.\n\nCette documentation permet à une équipe de reprendre un sujet sans dépendre d'une seule personne.",
                        'en' => "I prepare reports and progress statements on IT activities, and document interventions. Tracking relies on dedicated tools: tickets with status and history, inventory of equipment and licences.\n\nThis documentation lets a team pick up a topic without depending on a single person.",
                    ],
                    'tech' => ['GLPI', 'Zabbix'],
                ],
            ],
            'design' => [
                [
                    'name' => ['fr' => "Conception d'interfaces", 'en' => 'Interface design'],
                    'description' => [
                        'fr' => 'Maquettes, parcours utilisateur et systèmes de composants cohérents.',
                        'en' => 'Mockups, user flows and consistent component systems.',
                    ],
                    'details' => [
                        'fr' => "Je pense les interfaces comme des systèmes : des couleurs, des espacements et des composants réutilisables, plutôt que des écrans isolés. Ce portfolio en est l'exemple, avec un thème clair et un thème sombre qui partagent les mêmes règles.",
                        'en' => 'I think of interfaces as systems: colours, spacing and reusable components rather than isolated screens. This portfolio is the example, with a light and a dark theme sharing the same rules.',
                    ],
                    'tech' => ['Figma', 'TailwindCSS', 'React'],
                ],
                [
                    'name' => ['fr' => 'Animation et mouvement', 'en' => 'Animation and motion'],
                    'description' => [
                        'fr' => 'Transitions fluides et interactions au défilement, sans sacrifier les performances.',
                        'en' => 'Fluid transitions and scroll interactions, without sacrificing performance.',
                    ],
                    'details' => [
                        'fr' => "Le mouvement doit aider à comprendre, pas décorer : apparitions au défilement, texte qui s'écrit, cartes qui réagissent à la souris.\n\nJe veille aux performances (animations sur des propriétés peu coûteuses) et je respecte la préférence de mouvement réduit.",
                        'en' => "Motion should help understanding, not decorate: reveals on scroll, text that writes itself, cards that react to the mouse.\n\nI watch performance (animating cheap properties) and respect the reduced-motion preference.",
                    ],
                    'tech' => ['React', 'JS Vanilla'],
                ],
                [
                    'name' => ['fr' => 'Accessibilité', 'en' => 'Accessibility'],
                    'description' => [
                        'fr' => 'Contrastes, navigation au clavier et respect des préférences de mouvement réduit.',
                        'en' => 'Contrast, keyboard navigation and respect for reduced-motion preferences.',
                    ],
                    'details' => [
                        'fr' => "Un site doit rester utilisable par tous : contrastes suffisants dans les deux thèmes, éléments interactifs atteignables au clavier, fenêtres qui gèrent le focus, textes alternatifs, et animations qui s'adaptent aux préférences de l'utilisateur.",
                        'en' => 'A site must stay usable by everyone: sufficient contrast in both themes, keyboard-reachable interactive elements, dialogs that manage focus, alternative texts, and animations that adapt to the visitor\'s preferences.',
                    ],
                    'tech' => [],
                ],
            ],
        ];
    }
}
