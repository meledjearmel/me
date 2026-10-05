<?php

use App\Models\Post;
use App\Models\PostTag;

test('only published posts whose date has passed are public', function () {
    $published = Post::factory()->create();
    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();

    expect(Post::query()->published()->pluck('id')->all())->toBe([$published->id]);
});

test('the body keeps the editor formatting and drops anything unsafe', function () {
    $post = Post::factory()->create([
        'body' => [
            'fr' => '<h2 style="text-align: center">Titre</h2>'
                .'<p><strong>gras</strong> <mark>surligné</mark> <a href="javascript:alert(1)">piège</a></p>'
                .'<script>alert(1)</script><p onclick="alert(1)">clic</p>',
            'en' => '<p>Hello</p>',
        ],
    ]);

    expect($post->getTranslation('body', 'fr'))
        ->toContain('<h2 style="text-align: center;">Titre</h2>')
        ->toContain('<strong>gras</strong>')
        ->toContain('<mark>surligné</mark>')
        ->not->toContain('<script>')
        ->not->toContain('javascript:')
        ->not->toContain('onclick');
});

test('the reading time follows the longest translation', function () {
    $post = Post::factory()->create([
        'body' => ['fr' => '<p>'.str_repeat('mot ', 500).'</p>', 'en' => '<p>Short</p>'],
    ]);

    expect($post->reading_minutes)->toBe(3);
});

test('posts can be tagged', function () {
    $post = Post::factory()->hasAttached(PostTag::factory()->count(2), [], 'tags')->create();

    expect($post->tags)->toHaveCount(2);
});

test('a mention keeps only a known kind and a numeric id', function () {
    $post = Post::factory()->create([
        'body' => ['fr' => '<p><span data-type="mention" data-kind="project" data-id="3" data-label="App Station" onclick="alert(1)"></span>'
            .'<span data-type="mention" data-kind="user" data-id="1" data-label="Piège"></span></p>'],
    ]);

    expect($post->getTranslation('body', 'fr'))
        ->toContain('<span data-type="mention" data-kind="project" data-id="3" data-label="App Station">App Station</span>')
        ->not->toContain('onclick')
        ->not->toContain('data-kind="user"');
});
