<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Post;
use App\Models\PostTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            /**
             * L'anglais est facultatif : absent, le site affiche le français.
             *
             * @var array{fr: string, en?: string}
             */
            'title' => $this->getTranslations('title'),
            /** @var array{fr?: string, en?: string}|null */
            'excerpt' => $this->getTranslations('excerpt') ?: null,
            /**
             * Contenu en HTML, nettoyé par le serveur (balises de l'éditeur Tiptap de l'admin).
             *
             * @var array{fr: string, en?: string}
             */
            'body' => $this->getTranslations('body'),
            /** Temps de lecture estimé, en minutes. */
            'reading_minutes' => $this->reading_minutes,
            /** Lectures affichées sur le site : une par session toutes les 30 minutes, robots et aperçus exclus. */
            'views_count' => $this->views_count,
            'is_featured' => $this->is_featured,
            /** `draft` ou `published`. */
            'status' => $this->status,
            /** Date de publication ; dans le futur, l'article est programmé. */
            'published_at' => $this->published_at,
            /** L'article est visible sur le site (publié et date passée). */
            'is_live' => $this->isPublished(),
            /**
             * Lien signé vers l'aperçu de l'article sur le site (version française), valable
             * 72 heures : il s'ouvre sans compte, même pour un brouillon ou un article programmé.
             */
            'preview_url' => $this->previewUrl(),
            /**
             * Réactions anonymes des lecteurs : nombre de `like`, `love`, `fire`, `idea` et `think`.
             *
             * @var array{like: int, love: int, fire: int, idea: int, think: int}
             */
            'reactions' => $this->reactionCounts(),
            /**
             * Partages depuis le site, par réseau : un par session, article et réseau toutes les
             * 30 minutes, robots exclus. `copy` = lien copié, `native` = feuille de partage du téléphone.
             *
             * @var array{linkedin: int, x: int, whatsapp: int, facebook: int, email: int, copy: int, native: int}
             */
            'shares' => $this->shareCounts(),
            /** Total des partages, tous réseaux confondus. */
            'shares_count' => $this->sharesTotal(),
            /** Commentaires en attente de modération. */
            'pending_comments_count' => $this->comments()->where('status', 'pending')->count(),
            'cover_url' => $this->getFirstMediaUrl('cover') ?: null,
            /** @var list<string> */
            'tags' => $this->whenLoaded('tags', fn () => $this->tags
                ->map(fn (PostTag $tag): string => $tag->getTranslation('name', 'fr'))
                ->values()),
            /**
             * Nom français de la série de l'article (`null` hors série). À l'écriture, le champ
             * `series` range l'article dans la série de ce nom (créée au besoin) ; vide, il l'en sort.
             */
            'series' => $this->series?->getTranslation('name', 'fr'),
            /** Place de l'article dans sa série (1, 2, 3…). */
            'series_position' => $this->series_position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
