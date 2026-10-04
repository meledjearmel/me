<?php

use App\Ai\Agents\TextTranslator;
use App\Jobs\TranslatePostTag;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.post-tags.index'))->assertRedirect(route('login'));
});

test('the tags list and the edit page render', function () {
    $user = User::factory()->create();
    $tag = PostTag::factory()->create();

    $this->actingAs($user)->get(route('admin.post-tags.index'))
        ->assertInertia(fn ($page) => $page->component('admin/post-tags/index')->has('tags.data', 1));
    $this->actingAs($user)->get(route('admin.post-tags.edit', $tag))
        ->assertInertia(fn ($page) => $page->component('admin/post-tags/edit')->where('tag.id', $tag->id));
});

test('a tag can be renamed in both languages without changing its slug', function () {
    $tag = PostTag::factory()->create(['name' => ['fr' => 'Développement logiciel', 'en' => 'Développement logiciel'], 'slug' => 'developpement-logiciel']);

    $this->actingAs(User::factory()->create())->put(route('admin.post-tags.update', $tag), [
        'name' => ['fr' => 'Développement logiciel', 'en' => 'Software development'],
    ])->assertRedirect(route('admin.post-tags.index'));

    expect($tag->fresh())
        ->getTranslation('name', 'en')->toBe('Software development')
        ->slug->toBe('developpement-logiciel');
});

test('deleting a tag removes it from its posts', function () {
    $tag = PostTag::factory()->create();
    $post = Post::factory()->create();
    $post->tags()->attach($tag);

    $this->actingAs(User::factory()->create())->delete(route('admin.post-tags.destroy', $tag))
        ->assertRedirect(route('admin.post-tags.index'));

    expect(PostTag::query()->count())->toBe(0)
        ->and($post->tags()->count())->toBe(0);
});

test('a new tag is sent for translation, an existing one is not', function () {
    Queue::fake([TranslatePostTag::class]);
    PostTag::factory()->create(['name' => ['fr' => 'Laravel', 'en' => 'Laravel'], 'slug' => 'laravel']);

    $this->actingAs(User::factory()->create())->post(route('admin.posts.store'), [
        'title' => ['fr' => 'Article'],
        'slug' => 'article',
        'body' => ['fr' => '<p>Texte</p>'],
        'status' => 'draft',
        'tags' => ['Laravel', 'Développement logiciel'],
    ])->assertSessionHasNoErrors();

    Queue::assertPushed(TranslatePostTag::class, 1);
    Queue::assertPushed(TranslatePostTag::class, fn (TranslatePostTag $job): bool => $job->tag->slug === 'developpement-logiciel');
});

test('the translation job fills in the english name', function () {
    TextTranslator::fake(['Software development']);
    $tag = PostTag::factory()->create(['name' => ['fr' => 'Développement logiciel', 'en' => 'Développement logiciel']]);

    TranslatePostTag::dispatchSync($tag);

    expect($tag->fresh()->getTranslation('name', 'en'))->toBe('Software development');
});

test('the translation job never overwrites an english name corrected by hand', function () {
    TextTranslator::fake()->preventStrayPrompts();
    $tag = PostTag::factory()->create(['name' => ['fr' => 'Développement logiciel', 'en' => 'Software engineering']]);

    TranslatePostTag::dispatchSync($tag);

    expect($tag->fresh()->getTranslation('name', 'en'))->toBe('Software engineering');
});
