<?php

use App\Models\Post;
use App\Models\PostTag;

test('only published posts whose date has passed are public', function () {
    $published = Post::factory()->create();
    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();

    expect(Post::query()->published()->pluck('id')->all())->toBe([$published->id]);
});

test('the markdown body is rendered with raw html escaped', function () {
    $post = Post::factory()->create([
        'body' => ['fr' => "## Titre\n\n**gras** <script>alert(1)</script>", 'en' => 'Hello'],
    ]);

    $html = $post->bodyHtml('fr');

    expect($html)
        ->toContain('<h2>Titre</h2>')
        ->toContain('<strong>gras</strong>')
        ->not->toContain('<script>');
});

test('the reading time follows the longest translation', function () {
    $post = Post::factory()->create([
        'body' => ['fr' => str_repeat('mot ', 500), 'en' => 'Short'],
    ]);

    expect($post->reading_minutes)->toBe(3);
});

test('posts can be tagged', function () {
    $post = Post::factory()->hasAttached(PostTag::factory()->count(2), [], 'tags')->create();

    expect($post->tags)->toHaveCount(2);
});
