<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Situe une adresse IP (pays et ville) grâce à la base GeoLite2 de MaxMind,
 * installée par `php artisan geoip:update`. Sans base, ou pour une adresse
 * inconnue (réseau local, VPN…), la localisation est simplement vide :
 * elle ne bloque jamais ce qui l'appelle.
 */
class GeoLocator
{
    private ?Reader $reader = null;

    /**
     * @return array{country_code: string|null, country: string|null, city: string|null}
     */
    public function locate(?string $ip): array
    {
        $unknown = ['country_code' => null, 'country' => null, 'city' => null];

        if ($ip === null || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $unknown;
        }

        $reader = $this->reader();

        if ($reader === null) {
            return $unknown;
        }

        try {
            $record = $reader->city($ip);
        } catch (Throwable) {
            return $unknown;
        }

        return [
            'country_code' => $record->country->isoCode,
            'country' => $record->country->name,
            'city' => $record->city->name,
        ];
    }

    private function reader(): ?Reader
    {
        if ($this->reader !== null) {
            return $this->reader;
        }

        $path = (string) config('services.maxmind.database');

        if (! is_file($path)) {
            return null;
        }

        try {
            // Noms en français, avec l'anglais en repli.
            return $this->reader = new Reader($path, ['fr', 'en']);
        } catch (Throwable $exception) {
            Log::warning('Base GeoLite2 illisible', ['error' => $exception->getMessage()]);

            return null;
        }
    }
}
