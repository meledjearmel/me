<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Base GeoLite2 (localisation des téléchargements du CV).
Schedule::command('geoip:update')->weekly()->mondays()->at('04:00');
