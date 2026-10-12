<?php

namespace App\Mcp\Tools;

use App\Services\PublicUrlFetcher;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use RuntimeException;

#[Name('read-web-page')]
#[Description('Lit une page web publique (un article existant, une doc…) et renvoie son titre, sa description et son texte principal (titres, paragraphes, listes, code), pour s\'en inspirer. Ne jamais recopier : reformuler avec ses propres mots, ajouter son expérience, et citer la source avec un lien quand une idée ou un extrait en vient.')]
#[IsReadOnly]
#[IsOpenWorld]
class ReadWebPageTool extends Tool
{
    /** Au-delà, la page est tronquée : l'essentiel d'un article tient dans ce volume. */
    private const int MAX_TEXT_LENGTH = 20_000;

    private const int MAX_BYTES = 3 * 1_048_576;

    private const array NOISE = ['script', 'style', 'noscript', 'svg', 'nav', 'header', 'footer', 'aside', 'form', 'iframe', 'button'];

    public function handle(Request $request, PublicUrlFetcher $fetcher): Response
    {
        $request->validate(['url' => ['required', 'url:http,https', 'max:2000']]);

        try {
            $html = $fetcher->get($request->get('url'), self::MAX_BYTES)->body();
        } catch (RuntimeException $exception) {
            return Response::error($exception->getMessage());
        }

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);

        $meta = fn (string $query): ?string => trim((string) $xpath->evaluate("string({$query})")) ?: null;
        $title = $meta('//meta[@property="og:title"]/@content') ?? $meta('//title');
        $description = $meta('//meta[@name="description"]/@content') ?? $meta('//meta[@property="og:description"]/@content');
        $published = $meta('//meta[@property="article:published_time"]/@content');
        $author = $meta('//meta[@name="author"]/@content');

        foreach (self::NOISE as $tag) {
            foreach (iterator_to_array($document->getElementsByTagName($tag)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $main = $xpath->query('//article')->item(0) ?? $xpath->query('//main')->item(0) ?? $document->getElementsByTagName('body')->item(0);
        $text = $main === null ? '' : trim((string) preg_replace("/\n{3,}/", "\n\n", $this->text($main)));

        if ($text === '') {
            return Response::error('Aucun texte lisible sur cette page (contenu chargé par JavaScript ?).');
        }

        return Response::text(implode("\n", array_filter([
            "Source : {$request->get('url')}",
            $title ? "Titre : {$title}" : null,
            $description ? "Description : {$description}" : null,
            $author ? "Auteur : {$author}" : null,
            $published ? "Publié le : {$published}" : null,
            '',
            Str::limit($text, self::MAX_TEXT_LENGTH, "\n\n[… page tronquée]"),
        ], fn (?string $line): bool => $line !== null)));
    }

    /** Texte d'un nœud, avec les titres, listes et blocs de code marqués en Markdown. */
    private function text(DOMNode $node): string
    {
        if (! $node instanceof DOMElement) {
            return $node->nodeType === XML_TEXT_NODE ? (string) preg_replace('/\s+/', ' ', $node->textContent) : '';
        }

        $tag = strtolower($node->tagName);

        if ($tag === 'pre') {
            return "\n\n```\n".trim($node->textContent)."\n```\n\n";
        }

        $inner = '';

        foreach ($node->childNodes as $child) {
            $inner .= $this->text($child);
        }

        $inner = trim($inner);

        return match (true) {
            $inner === '' => '',
            in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) => "\n\n".str_repeat('#', (int) $tag[1])." {$inner}\n\n",
            $tag === 'li' => "\n- {$inner}",
            in_array($tag, ['p', 'div', 'section', 'blockquote', 'ul', 'ol', 'table', 'tr', 'figure'], true) => "\n\n{$inner}\n\n",
            $tag === 'code' => "`{$inner}`",
            $tag === 'a' && filled($href = $node->getAttribute('href')) && str_starts_with($href, 'http') => "{$inner} ({$href})",
            default => " {$inner} ",
        };
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('Adresse http(s) publique de la page à lire.')->required(),
        ];
    }
}
