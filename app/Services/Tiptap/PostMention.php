<?php

namespace App\Services\Tiptap;

use App\Services\PostMentions;
use Tiptap\Core\Node;

/**
 * Mention d'un élément du site dans un article (« @App Station »), pendant serveur du nœud
 * de l'éditeur : `<span data-type="mention" data-kind="project" data-id="12" data-label="…">`.
 * Un type inconnu ou un identifiant invalide ne passe pas le nettoyage.
 */
class PostMention extends Node
{
    public static $name = 'mention';

    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'span[data-type="mention"]',
                'getAttrs' => fn ($DOMNode): bool => in_array($DOMNode->getAttribute('data-kind'), PostMentions::KINDS, true)
                    && ctype_digit($DOMNode->getAttribute('data-id')),
            ],
        ];
    }

    public function addAttributes(): array
    {
        return [
            'kind' => [
                'parseHTML' => fn ($DOMNode): string => $DOMNode->getAttribute('data-kind'),
                'renderHTML' => fn ($attributes): array => ['data-kind' => $attributes->kind ?? null],
            ],
            'id' => [
                'parseHTML' => fn ($DOMNode): int => (int) $DOMNode->getAttribute('data-id'),
                'renderHTML' => fn ($attributes): array => ['data-id' => $attributes->id ?? null],
            ],
            'label' => [
                'parseHTML' => fn ($DOMNode): string => $DOMNode->getAttribute('data-label') ?: trim(ltrim($DOMNode->textContent, '@')),
                'renderHTML' => fn ($attributes): array => ['data-label' => $attributes->label ?? null],
            ],
        ];
    }

    public function renderText($node): string
    {
        return $node->attrs->label ?? '';
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        return ['span', ['data-type' => 'mention', ...$HTMLAttributes]];
    }
}
