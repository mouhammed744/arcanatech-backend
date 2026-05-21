<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| ARCANA TECH - Tâches automatisées
|--------------------------------------------------------------------------
|
| Configuration des tâches planifiées via le scheduler Laravel.
| Pour activer : ajouter cette entrée cron sur le serveur :
|
|   * * * * * cd /chemin/vers/management_universiy && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Chaque vendredi à 18h : rapport hebdomadaire aux admins
Schedule::command('arcana:weekly-report')
    ->weeklyOn(5, '18:00') // Vendredi à 18h
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/weekly-report.log'));
