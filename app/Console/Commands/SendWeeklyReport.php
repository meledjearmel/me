<?php

namespace App\Console\Commands;

use App\Services\WeeklyReport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Envoie le rapport de la semaine à l'email du profil. Planifiée chaque lundi matin ; rien ne
 * part si le rapport est désactivé, déjà envoyé dans la semaine ou si la semaine est vide,
 * sauf avec --force.
 */
#[Signature('report:weekly {--force : Envoyer même si désactivé, déjà envoyé ou vide}')]
#[Description('Envoie le rapport de la semaine du portfolio par email')]
class SendWeeklyReport extends Command
{
    public function handle(WeeklyReport $report): int
    {
        $status = $report->send((bool) $this->option('force'));

        $this->info(match ($status) {
            'sent' => 'Rapport envoyé.',
            'disabled' => 'Rapport désactivé dans l\'administration : rien envoyé.',
            'already_sent' => 'Rapport déjà envoyé cette semaine.',
            'empty' => 'Semaine sans activité : rien envoyé.',
            'no_recipient' => 'Aucun email sur le profil : rien envoyé.',
        });

        return self::SUCCESS;
    }
}
