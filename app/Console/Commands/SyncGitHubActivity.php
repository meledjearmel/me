<?php

namespace App\Console\Commands;

use App\Services\GitHubActivity;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Met à jour le cache de mon activité GitHub affichée sur la page À propos.
 * Planifiée toutes les heures ; un échec garde la dernière version connue.
 */
#[Signature('github:sync')]
#[Description('Met à jour le cache de l’activité GitHub affichée sur le site')]
class SyncGitHubActivity extends Command
{
    public function handle(GitHubActivity $activity): int
    {
        if ($activity->username() === null) {
            $this->warn('Aucun lien GitHub sur le profil : rien à synchroniser.');

            return self::SUCCESS;
        }

        if (! $activity->sync()) {
            $this->error('GitHub n’a pas répondu : la dernière version connue est conservée.');

            return self::FAILURE;
        }

        $this->info('Activité GitHub mise à jour.');

        return self::SUCCESS;
    }
}
