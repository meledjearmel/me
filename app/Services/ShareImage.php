<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Profile;
use GdImage;
use Illuminate\Support\Facades\Storage;

/**
 * Image de partage (Open Graph, 1200 × 630) d'un article sans couverture : son titre
 * sur le fond bleu nuit du site, dans ses polices. LinkedIn et X n'acceptant pas le SVG,
 * elle est dessinée en PNG avec GD.
 *
 * Le fichier est mis en cache ; son nom dépend du contenu affiché, si bien qu'un titre
 * modifié produit une nouvelle image (et les réseaux la récupèrent à nouveau).
 */
class ShareImage
{
    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    /** À changer quand le dessin change, pour régénérer les images déjà en cache. */
    private const int VERSION = 1;

    private const string DIRECTORY = 'share-images';

    private const int MARGIN = 80;

    private const int MAX_TITLE_LINES = 4;

    /** Chemin du PNG de l'article dans la langue donnée, dessiné s'il n'existe pas encore. */
    public function forPost(Post $post, string $locale): string
    {
        $title = $post->getTranslation('title', $locale);
        $owner = Profile::query()->value('name') ?? (string) config('app.name');
        $footer = $locale === 'en'
            ? "{$post->reading_minutes} min read · ".$this->domain()
            : "{$post->reading_minutes} min de lecture · ".$this->domain();

        $path = self::DIRECTORY.'/'.md5(implode('|', [self::VERSION, $title, $owner, $footer])).'.png';
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            $disk->put($path, $this->draw($title, mb_strtoupper("Blog · {$owner}"), $footer));
        }

        return $disk->path($path);
    }

    private function draw(string $title, string $eyebrow, string $footer): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $night = $this->color($image, '#0d1328');
        $gold = $this->color($image, '#ffda3f');
        $cream = $this->color($image, '#fff9f1');
        $muted = $this->color($image, '#9aa3bd');

        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $night);
        // Filet doré à gauche, comme l'accent des titres du site.
        imagefilledrectangle($image, 0, 0, 12, self::HEIGHT, $gold);

        $display = resource_path('fonts/DMSerifDisplay-Regular.ttf');
        $sans = resource_path('fonts/SpaceGrotesk.ttf');

        imagettftext($image, 22, 0, self::MARGIN, 120, $gold, $sans, $eyebrow);

        [$size, $lines] = $this->fitTitle($title, $display);
        $lineHeight = (int) round($size * 1.18);
        $top = 200 + $size;

        foreach ($lines as $index => $line) {
            imagettftext($image, $size, 0, self::MARGIN, $top + $index * $lineHeight, $cream, $display, $line);
        }

        imagettftext($image, 22, 0, self::MARGIN, self::HEIGHT - 70, $muted, $sans, $footer);

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * La plus grande taille de police où le titre tient en quatre lignes ; au-delà,
     * la dernière ligne se termine par « … ».
     *
     * @return array{0: int, 1: list<string>}
     */
    private function fitTitle(string $title, string $font): array
    {
        $maxWidth = self::WIDTH - 2 * self::MARGIN;

        foreach ([64, 56, 48, 42] as $size) {
            $lines = $this->wrap($title, $font, $size, $maxWidth);

            if (count($lines) <= self::MAX_TITLE_LINES) {
                return [$size, $lines];
            }
        }

        $lines = array_slice($lines, 0, self::MAX_TITLE_LINES);
        $lines[self::MAX_TITLE_LINES - 1] = rtrim($lines[self::MAX_TITLE_LINES - 1], ' ,.;:').' …';

        return [$size, $lines];
    }

    /** @return list<string> */
    private function wrap(string $text, string $font, int $size, int $maxWidth): array
    {
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            $candidate = $current === '' ? $word : "{$current} {$word}";

            if ($current !== '' && $this->width($candidate, $font, $size) > $maxWidth) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function width(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box === false ? 0 : abs($box[2] - $box[0]);
    }

    private function color(GdImage $image, string $hex): int
    {
        [$red, $green, $blue] = sscanf($hex, '#%02x%02x%02x');

        return (int) imagecolorallocate($image, $red, $green, $blue);
    }

    private function domain(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
