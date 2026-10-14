<?php

namespace App\Services\Syndication;

use App\Enums\SyndicationMode;
use App\Enums\SyndicationPlatform;
use App\Models\SiteSetting;
use RuntimeException;

/**
 * Les réglages de republication, sans jamais exposer les clés, et leur enregistrement :
 * une clé vide garde l'ancienne, et passer en mode auto note la date d'activation (les
 * articles plus anciens ne partent pas d'eux-mêmes).
 */
class SyndicationSettings
{
    public function __construct(private readonly HashnodeClient $hashnode) {}

    /**
     * @return array<string, array{mode: string, has_key: bool, locale: string, auto_since: string|null, publication_id?: string|null}>
     */
    public function toArray(): array
    {
        $settings = SiteSetting::current();

        return collect(SyndicationPlatform::cases())->mapWithKeys(fn (SyndicationPlatform $platform): array => [$platform->value => [
            'mode' => ($settings->{"{$platform->value}_mode"} ?? SyndicationMode::Off)->value,
            'has_key' => filled($settings->{"{$platform->value}_api_key"}),
            'locale' => $settings->{"{$platform->value}_locale"} ?: 'en',
            'auto_since' => $settings->{"{$platform->value}_auto_since"}?->toIso8601String(),
            ...($platform === SyndicationPlatform::Hashnode ? ['publication_id' => $settings->hashnode_publication_id] : []),
        ]])->all();
    }

    /**
     * @param  array<string, mixed>  $data  Voir SyndicationSettingsRequest.
     * @return string|null L'erreur de Hashnode si l'identifiant de publication n'a pas pu être retrouvé.
     */
    public function update(array $data): ?string
    {
        $settings = SiteSetting::current();
        $changes = [];

        foreach (SyndicationPlatform::cases() as $platform) {
            $key = $platform->value;
            $mode = SyndicationMode::from($data["{$key}_mode"]);

            $changes["{$key}_mode"] = $mode;
            $changes["{$key}_locale"] = $data["{$key}_locale"];

            if (! empty($data["{$key}_clear_key"])) {
                $changes["{$key}_api_key"] = null;
            } elseif (filled($data["{$key}_api_key"] ?? null)) {
                $changes["{$key}_api_key"] = trim($data["{$key}_api_key"]);
            }

            if ($mode === SyndicationMode::Auto && $settings->{"{$key}_mode"} !== SyndicationMode::Auto) {
                $changes["{$key}_auto_since"] = now();
            } elseif ($mode !== SyndicationMode::Auto) {
                $changes["{$key}_auto_since"] = null;
            }
        }

        if (array_key_exists('hashnode_publication_id', $data)) {
            $changes['hashnode_publication_id'] = filled($data['hashnode_publication_id']) ? trim($data['hashnode_publication_id']) : null;
        }

        $settings->update($changes);

        return $this->detectHashnodePublication($settings->refresh());
    }

    /**
     * Sans identifiant de publication, le retrouve avec le jeton : la première publication du
     * compte (le choix se change ensuite dans la liste). Un jeton refusé n'empêche pas
     * d'enregistrer : l'erreur est rendue pour l'afficher.
     */
    public function detectHashnodePublication(SiteSetting $settings): ?string
    {
        if (blank($settings->hashnode_api_key) || filled($settings->hashnode_publication_id)) {
            return null;
        }

        try {
            $first = $this->hashnode->publications((string) $settings->hashnode_api_key)[0] ?? null;
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        }

        if ($first === null) {
            return 'Hashnode : aucune publication sur ce compte. Créez d\'abord un blog sur hashnode.com.';
        }

        $settings->update(['hashnode_publication_id' => $first['id']]);

        return null;
    }

    /**
     * Les publications Hashnode du compte, pour choisir celle où republier ; vide sans jeton
     * ou si Hashnode ne répond pas.
     *
     * @return list<array{id: string, title: string, url: string}>
     */
    public function hashnodePublications(): array
    {
        $token = SiteSetting::current()->hashnode_api_key;

        if (blank($token)) {
            return [];
        }

        try {
            return $this->hashnode->publications((string) $token);
        } catch (RuntimeException) {
            return [];
        }
    }
}
