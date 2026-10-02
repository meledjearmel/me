<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PharData;
use Throwable;

/**
 * Télécharge la base GeoLite2-City de MaxMind (localisation des
 * téléchargements du CV). MaxMind la met à jour deux fois par semaine ;
 * la commande est planifiée chaque semaine.
 */
#[Signature('geoip:update {--if-missing : Ne rien faire si la base est déjà installée}')]
#[Description('Télécharge ou met à jour la base GeoLite2-City de MaxMind')]
class UpdateGeoIpDatabase extends Command
{
    private const DOWNLOAD_URL = 'https://download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz';

    public function handle(): int
    {
        $target = (string) config('services.maxmind.database');

        if ($this->option('if-missing') && is_file($target)) {
            $this->info('Base GeoLite2 déjà installée.');

            return self::SUCCESS;
        }

        $accountId = config('services.maxmind.account_id');
        $licenseKey = config('services.maxmind.license_key');

        if (! $accountId || ! $licenseKey) {
            $this->warn('MAXMIND_ACCOUNT_ID et MAXMIND_LICENSE_KEY ne sont pas renseignés : base non téléchargée.');

            return self::SUCCESS;
        }

        $workDir = storage_path('app/geoip/tmp-'.uniqid());
        File::ensureDirectoryExists($workDir);
        $archive = $workDir.'/GeoLite2-City.tar.gz';

        try {
            Http::withBasicAuth((string) $accountId, (string) $licenseKey)
                ->timeout(120)
                ->sink($archive)
                ->get(self::DOWNLOAD_URL)
                ->throw();

            (new PharData($archive))->decompress();
            (new PharData(substr($archive, 0, -3)))->extractTo($workDir);

            $database = collect(File::allFiles($workDir))
                ->first(fn ($file): bool => $file->getFilename() === 'GeoLite2-City.mmdb');

            if ($database === null) {
                $this->error("L'archive ne contient pas GeoLite2-City.mmdb.");

                return self::FAILURE;
            }

            File::ensureDirectoryExists(dirname($target));
            // Remplacement en une fois : les requêtes en cours ne lisent jamais une base à moitié copiée.
            File::move($database->getPathname(), $target.'.new');
            File::move($target.'.new', $target);
        } catch (Throwable $exception) {
            $this->error('Téléchargement impossible : '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            File::deleteDirectory($workDir);
        }

        $this->info('Base GeoLite2-City à jour.');

        return self::SUCCESS;
    }
}
