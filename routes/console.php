<?php

use App\Models\McpActivity;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Base GeoLite2 (localisation des téléchargements du CV).
Schedule::command('geoip:update')->weekly()->mondays()->at('04:00');

// Rappel des rendez-vous confirmés, la veille.
Schedule::command('appointments:send-reminders')->hourly();

// Annonce des nouveaux articles aux abonnés de la newsletter.
Schedule::command('newsletter:send')->everyFifteenMinutes();

// Activité GitHub de la page À propos.
Schedule::command('github:sync')->hourly();

// Journal d'activité du serveur MCP (90 jours) et jetons OAuth expirés des connecteurs.
Schedule::command('model:prune', ['--model' => [McpActivity::class]])->daily()->at('03:30');
Schedule::command('passport:purge')->daily()->at('03:45');
