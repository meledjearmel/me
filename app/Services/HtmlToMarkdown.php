<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Convertit le HTML d'un article (celui de l'éditeur Tiptap, déjà nettoyé) en Markdown pour
 * dev.to et Hashnode : titres, emphases, code (avec son langage), liens et images en adresses
 * absolues, listes (y compris les listes de tâches), citations, tableaux et séparateurs.
 */
class HtmlToMarkdown
{
    private string $baseUrl = '';

    public function convert(string $html, string $baseUrl): string
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8"><div id="md-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $document->getElementById('md-root');

        $markdown = $root === null ? '' : $this->blocks($root);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $markdown))."\n";
    }

    /** Les enfants d'un élément de bloc, séparés par une ligne vide. */
    private function blocks(DOMNode $parent, string $indent = ''): string
    {
        $output = '';

        foreach ($parent->childNodes as $node) {
            $output .= $this->block($node, $indent);
        }

        return $output;
    }

    private function block(DOMNode $node, string $indent): string
    {
        if ($node instanceof DOMText) {
            $text = trim($node->textContent);

            return $text === '' ? '' : $indent.$this->escape($text)."\n\n";
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        return match (strtolower($node->nodeName)) {
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => str_repeat('#', (int) substr($node->nodeName, 1)).' '.trim($this->inline($node))."\n\n",
            'p' => ($text = trim($this->inline($node))) === '' ? '' : $indent.$text."\n\n",
            'pre' => $this->codeBlock($node, $indent),
            'blockquote' => collect(explode("\n", trim($this->blocks($node))))
                ->map(fn (string $line): string => $indent.'> '.$line)
                ->implode("\n")."\n\n",
            'ul', 'ol' => $this->list($node, $indent)."\n",
            'hr' => "---\n\n",
            'img' => $indent.$this->image($node)."\n\n",
            'table' => $this->table($node)."\n\n",
            'div', 'section', 'figure' => $this->blocks($node, $indent),
            default => ($text = trim($this->inline($node))) === '' ? '' : $indent.$text."\n\n",
        };
    }

    private function codeBlock(DOMElement $pre, string $indent): string
    {
        $code = $pre->getElementsByTagName('code')->item(0) ?? $pre;
        preg_match('/language-([\w+#-]+)/', (string) $code->getAttribute('class'), $match);
        $body = rtrim($code->textContent, "\n");
        $fence = str_contains($body, '```') ? '~~~' : '```';

        return collect(explode("\n", $fence.($match[1] ?? '')."\n".$body."\n".$fence))
            ->map(fn (string $line): string => $indent.$line)
            ->implode("\n")."\n\n";
    }

    private function list(DOMElement $list, string $indent): string
    {
        $ordered = strtolower($list->nodeName) === 'ol';
        $isTasks = $list->getAttribute('data-type') === 'taskList';
        $number = (int) ($list->getAttribute('start') ?: 1);
        $lines = '';

        foreach ($list->childNodes as $item) {
            if (! $item instanceof DOMElement || strtolower($item->nodeName) !== 'li') {
                continue;
            }

            $marker = match (true) {
                $isTasks => '- ['.($item->getAttribute('data-checked') === 'true' ? 'x' : ' ').'] ',
                $ordered => ($number++).'. ',
                default => '- ',
            };
            $inner = '';
            $nested = '';

            foreach ($item->childNodes as $child) {
                if ($child instanceof DOMElement && in_array(strtolower($child->nodeName), ['ul', 'ol'], true)) {
                    $nested .= $this->list($child, $indent.'  ');
                } elseif ($child instanceof DOMElement && in_array(strtolower($child->nodeName), ['label', 'input'], true)) {
                    continue;
                } else {
                    $inner .= ' '.($child instanceof DOMElement && in_array(strtolower($child->nodeName), ['p', 'div'], true)
                        ? $this->inline($child)
                        : $this->inline($child, true));
                }
            }

            $lines .= $indent.$marker.trim((string) preg_replace('/\s+/', ' ', $inner))."\n".$nested;
        }

        return $lines;
    }

    private function table(DOMElement $table): string
    {
        $rows = [];

        foreach ($table->getElementsByTagName('tr') as $row) {
            $cells = [];

            foreach ($row->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array(strtolower($cell->nodeName), ['td', 'th'], true)) {
                    $cells[] = str_replace('|', '\|', trim((string) preg_replace('/\s+/', ' ', $this->inline($cell))));
                }
            }

            $rows[] = $cells;
        }

        if ($rows === []) {
            return '';
        }

        $width = max(array_map('count', $rows));
        $line = fn (array $cells): string => '| '.implode(' | ', array_pad($cells, $width, '')).' |';

        return implode("\n", [
            $line($rows[0]),
            '|'.str_repeat(' --- |', $width),
            ...array_map($line, array_slice($rows, 1)),
        ]);
    }

    /** Le contenu d'un élément en ligne : emphases, code, liens, images, retours à la ligne. */
    private function inline(DOMNode $node, bool $self = false): string
    {
        if ($node instanceof DOMText) {
            return $this->escape((string) preg_replace('/\s+/', ' ', $node->textContent));
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $children = '';

        foreach ($node->childNodes as $child) {
            $children .= $this->inline($child);
        }

        if (! $self && in_array(strtolower($node->nodeName), ['p', 'div', 'li', 'td', 'th', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
            return $children;
        }

        return match (strtolower($node->nodeName)) {
            'strong', 'b' => $this->wrap($children, '**'),
            'em', 'i' => $this->wrap($children, '_'),
            's', 'del', 'strike' => $this->wrap($children, '~~'),
            'code' => '`'.str_replace('`', '\`', $node->textContent).'`',
            'a' => '['.trim($children).']('.$this->absolute($node->getAttribute('href')).')',
            'img' => $this->image($node),
            'br' => "  \n",
            default => $children,
        };
    }

    private function image(DOMElement $image): string
    {
        return '!['.str_replace(['[', ']'], '', $image->getAttribute('alt')).']('.$this->absolute($image->getAttribute('src')).')';
    }

    private function wrap(string $text, string $mark): string
    {
        $trimmed = trim($text);

        return $trimmed === '' ? $text : $mark.$trimmed.$mark;
    }

    private function absolute(string $url): string
    {
        return match (true) {
            $url === '' => '',
            str_starts_with($url, '//') => 'https:'.$url,
            str_starts_with($url, '/') => $this->baseUrl.$url,
            default => $url,
        };
    }

    /** Échappe ce qui ferait de la mise en forme involontaire au début d'une ligne ou dans le texte. */
    private function escape(string $text): string
    {
        return str_replace(['\\', '*', '_', '`'], ['\\\\', '\*', '\_', '\`'], $text);
    }
}
