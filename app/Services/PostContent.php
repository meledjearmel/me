<?php

namespace App\Services;

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
