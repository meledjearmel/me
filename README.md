# Portfolio d'Armel Meledje

Site portfolio bilingue (français / anglais) avec son espace d'administration.
Le site public présente le parcours, les compétences, les projets et les avis ;
l'administration permet de tout gérer sans toucher au code.

## Stack

- **Backend** : Laravel 13, PHP 8.5, SQLite par défaut
- **Frontend** : React + TypeScript via Inertia v3, Vite, Tailwind CSS v4
- **Admin** : shadcn/ui (Radix), graphiques Recharts
- **Site public** : CSS dédié (`resources/css/public.css`), Framer Motion
- **Paquets clés** : Spatie Translatable et MediaLibrary, Fortify (auth, 2FA, passkeys), Wayfinder, barryvdh/laravel-dompdf
- **Tests / qualité** : Pest, Laravel Pint

## Fonctionnalités

**Site public** (`/fr`, `/en`)
- Accueil, à propos, compétences, projets et page de chaque projet, contact, avis
- Bouton flottant : laisser un avis, proposer une collaboration (freelance ou embauche), remonter en haut
- Une demande d'embauche génère le CV adapté au profil métier choisi, en PDF, et l'envoie par mail
- Transitions de page et de thème animées, contenu traduit par langue

**Administration** (`/admin`, connexion requise)
- CRUD complet : profil, domaines, technologies, profils métier, compétences, formations, expériences, projets, références
- Modération des avis (dont 3 avis « à la une » maximum), messages de contact et demandes de collaboration
- Statut de publication (brouillon / publié) sur domaines, profils métier, compétences, formations et expériences : un brouillon n'apparaît ni sur le site ni sur le CV
- Listes paginées avec recherche et filtres, l'état étant conservé dans l'URL
- Page de détail pour chaque ressource
- Tableau de bord : audience, contenu, points à compléter, derniers messages

## Installation

```bash
composer setup
```

Cette commande installe les dépendances PHP et JS, crée le `.env`, génère la clé,
lance les migrations et compile le front. Ensuite :

```bash
php artisan db:seed        # contenu de départ (profil, projets, compétences…)
composer dev               # serveur, file d'attente, logs et Vite
```

Avec Laravel Herd, le site est servi sur `https://me.test`.

Le seeder crée le compte administrateur `me@armeldev.xyz` (sans jamais l'écraser) avec le
mot de passe `ADMIN_PASSWORD` du `.env` ; s'il est vide, un mot de passe aléatoire est
affiché une seule fois dans le terminal. Changez-le après la première connexion.
Les données fictives (avis d'exemple) ne sont ajoutées qu'en environnement `local`.

## Configuration

Variables utiles du `.env` :

| Variable | Rôle |
| --- | --- |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | Langue par défaut (`fr`) |
| `QUEUE_CONNECTION` | `database` : les mails partent par la file d'attente |
| `MAIL_*` | Serveur SMTP ; en local, Mailpit (`127.0.0.1:1025`, interface sur le port 8025) |

En production, il faut configurer `MAIL_*` et garder un worker actif :

```bash
php artisan queue:work
```

## Commandes utiles

```bash
php artisan test --compact              # tests
vendor/bin/pint --dirty --format agent  # style PHP
npx tsc --noEmit                        # types TypeScript
npm run build                           # build de production
php artisan wayfinder:generate --with-form   # régénère les routes TypeScript
```

## Structure

- `app/Http/Controllers/` : pages publiques ; `Admin/` : administration
- `app/Concerns/` : traits partagés (pagination et filtres des listes, statut de publication)
- `app/Services/` : génération du CV, données du tableau de bord
- `resources/js/pages/` : pages Inertia (`public/`, `admin/`, `auth/`, `settings/`)
- `resources/js/components/ui/` : composants shadcn ; `components/admin/` : briques de l'admin (listes, pages de détail, suppression)
- `lang/` : traductions françaises de l'interface et des validations
- `tests/Feature/` : tests Pest
