<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;
use Tempest\Highlight\Highlighter;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Extensions\TextAlign;
use Tiptap\Marks\Highlight;
use Tiptap\Marks\Link;
use Tiptap\Marks\Subscript;
use Tiptap\Marks\Superscript;
use Tiptap\Marks\Underline;
use Tiptap\Nodes\Image;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;
use Tiptap\Nodes\TaskItem;
use Tiptap\Nodes\TaskList;

/**
 * Contenu HTML des articles, produit par l'éditeur Tiptap de l'admin. Le passer par le
 * même schéma côté serveur ne garde que les nœuds, marques et attributs connus :
 * c'est ce qui le nettoie (pas de script, pas de lien javascript:, pas de style arbitraire).
 */
class PostContent
{
    public function sanitize(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        return $this->editor()->setContent($html)->getHTML();
    }

    /** Texte brut, pour le temps de lecture, les extraits et l'IA. */
    public function text(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        return trim($this->editor()->setContent($html)->getText(['blockSeparator' => "\n\n"]));
    }

    /**
     * Prépare le contenu pour la lecture : ancre (id) sur les titres h2 et h3 pour le sommaire,
     * coloration des blocs de code dont le langage est connu (`language-php`…).
     *
     * @return array{html: string, toc: list<array{id: string, text: string, level: int}>}
     */
    public function forReading(string $html): array
    {
        if (blank($html)) {
            return ['html' => '', 'toc' => []];
        }

        $document = new DOMDocument;
        // Enveloppe UTF-8 : sans elle, DOMDocument lit le HTML en ISO-8859-1.
        @$document->loadHTML('<?xml encoding="UTF-8"><div id="post-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $root = $document->getElementById('post-root');
        $toc = [];
        $used = [];

        $xpath = new DOMXPath($document);

        foreach ($xpath->query('//h2 | //h3') as $heading) {
            $text = trim($heading->textContent);
            $base = Str::slug($text) ?: 'section';
            $id = $base;

            for ($index = 2; in_array($id, $used, true); $index++) {
                $id = "{$base}-{$index}";
            }

            $used[] = $id;
            $heading->setAttribute('id', $id);
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) substr($heading->nodeName, 1)];
        }

        $this->highlightCode($document, $xpath);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return ['html' => $output, 'toc' => $toc];
    }

    /**
     * Remplace le texte des blocs de code par sa version colorée (spans `hl-*`) et note le
     * langage sur le `<pre>` pour l'étiquette affichée au lecteur.
     */
    private function highlightCode(DOMDocument $document, DOMXPath $xpath): void
    {
        $highlighter = new Highlighter;
        $supported = $highlighter->getSupportedLanguageNames();

        foreach ($xpath->query('//pre/code[starts-with(@class, "language-")]') as $code) {
            $language = Str::after($code->getAttribute('class'), 'language-');

            if (! in_array($language, $supported, true)) {
                continue;
            }

            $fragment = $document->createDocumentFragment();

            if (! @$fragment->appendXML($highlighter->parse($code->textContent, $language))) {
                continue;
            }

            while ($code->firstChild !== null) {
                $code->removeChild($code->firstChild);
            }

            $code->appendChild($fragment);
            $code->parentNode->setAttribute('data-language', $language);
        }
    }

    private function editor(): Editor
    {
        return new Editor([
            'extensions' => [
                new StarterKit,
                new Underline,
                new Link,
                new Highlight,
                new Subscript,
                new Superscript,
                new Image,
                new Table,
                new TableRow,
                new TableHeader,
                new TableCell,
                new TaskList,
                new TaskItem,
                new TextAlign(['types' => ['heading', 'paragraph']]),
            ],
        ]);
    }
}
