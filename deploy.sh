#!/usr/bin/env bash
# Déploiement du portfolio en production.
# Usage : ./deploy.sh  (depuis /srv/projects/me)

set -euo pipefail

cd "$(dirname "$0")"

echo "Nettoyage des caches (Wayfinder doit lire les routes à jour pendant le build)"
php artisan optimize:clear

echo "→ Récupération du code"
git pull --ff-only

echo "→ Dépendances PHP"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Dépendances Node"
bun install

echo "→ Vidage des caches (Wayfinder doit lire les routes à jour pendant le build)"
php artisan optimize:clear

echo "→ Build (client + SSR)"
bun run build:ssr

echo "→ Base de données"
php artisan migrate --force

echo "→ Base GeoLite2 (si absente)"
php artisan geoip:update --if-missing

echo "→ Caches Laravel"
php artisan optimize:clear
php artisan optimize

echo "→ Redémarrage des processus (queue, scheduler, reverb, SSR)"
php artisan queue:restart
pm2 startOrReload ecosystem.config.cjs
pm2 save

echo "✓ Déploiement terminé"
pm2 status
