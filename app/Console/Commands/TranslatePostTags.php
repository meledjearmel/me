<?php

namespace App\Console\Commands;

use App\Jobs\TranslatePostTag;
use App\Models\PostTag;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Lance la traduction anglaise des tags du blog dont le nom anglais recopie
 * encore le français (tags créés avant la traduction automatique).
 */
#[Signature('blog:translate-tags')]
#[Description('Traduit en anglais les tags du blog qui ne le sont pas encore')]
class TranslatePostTags extends Command
{
    public function handle(): int
    {
        $tags = PostTag::query()->get()
            ->filter(fn (PostTag $tag): bool => $tag->getTranslation('name', 'en', false) === $tag->getTranslation('name', 'fr'));

        $tags->each(fn (PostTag $tag) => TranslatePostTag::dispatch($tag));

        $this->info("{$tags->count()} tag(s) à traduire envoyé(s) dans la file d'attente.");

        return self::SUCCESS;
    }
}
