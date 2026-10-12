<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Télécharge une adresse publique pour le serveur MCP (page à lire, image à joindre) en
 * refusant le réseau interne (localhost, IP privées ou réservées), y compris après une
 * redirection : une IA ne doit pas pouvoir faire lire au serveur ses propres services.
 */
class PublicUrlFetcher
{
    private const int MAX_REDIRECTS = 3;

    private const int TIMEOUT = 15;

    /**
     * @throws RuntimeException Adresse refusée, injoignable ou trop lourde.
     */
    public function get(string $url, int $maxBytes): Response
    {
        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            $this->ensurePublic($url);

            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->withUserAgent('Mozilla/5.0 (compatible; PortfolioMcp/1.0)')
                    ->withOptions(['allow_redirects' => false])
                    ->get($url);
            } catch (ConnectionException) {
                throw new RuntimeException("Adresse injoignable : {$url}");
            }

            if ($response->redirect() && filled($location = $response->header('Location'))) {
                $url = $this->absolute($location, $url);

                continue;
            }

            if (! $response->successful()) {
                throw new RuntimeException("L'adresse a répondu {$response->status()} : {$url}");
            }

            if (strlen($response->body()) > $maxBytes) {
                throw new RuntimeException('Contenu trop lourd ('.round($maxBytes / 1_048_576, 1).' Mo au plus).');
            }

            return $response;
        }

        throw new RuntimeException('Trop de redirections.');
    }

    /**
     * @throws RuntimeException
     */
    private function ensurePublic(string $url): void
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? null;

        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || blank($host)) {
            throw new RuntimeException('Seules les adresses http(s) sont acceptées.');
        }

        $host = trim($host, '[]');
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);

        if ($addresses === []) {
            throw new RuntimeException("Nom de domaine introuvable : {$host}");
        }

        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('Adresse interne refusée.');
            }
        }
    }

    private function absolute(string $location, string $base): string
    {
        if (preg_match('~^https?://~i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = "{$parts['scheme']}://{$parts['host']}".(isset($parts['port']) ? ":{$parts['port']}" : '');

        return str_starts_with($location, '/') ? $origin.$location : $origin.'/'.$location;
    }
}
