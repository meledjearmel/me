<?php

use App\Enums\PublicationStatus;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
});

test('the posts list and the editor pages render', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user)->get(route('admin.posts.index'))->assertOk();
    $this->actingAs($user)->get(route('admin.posts.create'))->assertOk();
    $this->actingAs($user)->get(route('admin.posts.edit', $post))
        ->assertInertia(fn ($page) => $page->component('admin/posts/edit')->where('post.id', $post->id));
});

test('a post is created with its tags, cover and a sanitized body', function () {
    Storage::fake('public');
    PostTag::factory()->create(['name' => ['fr' => 'Laravel', 'en' => 'Laravel'], 'slug' => 'laravel']);

    $this->actingAs(User::factory()->create())->post(route('admin.posts.store'), [
        'title' => ['fr' => 'Mon article', 'en' => ''],
        'slug' => 'mon-article',
        'body' => ['fr' => '<p>Bonjour</p><script>alert(1)</script>', 'en' => ''],
        'status' => 'published',
        'tags' => ['Laravel', 'Inertia'],
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ])->assertSessionHasNoErrors();

    $post = Post::query()->where('slug', 'mon-article')->firstOrFail();

    expect($post->getTranslation('body', 'fr'))->toBe('<p>Bonjour</p>')
        ->and($post->getTranslations('title'))->toBe(['fr' => 'Mon article'])
        ->and($post->published_at)->not->toBeNull()
        ->and($post->tags->pluck('slug')->sort()->values()->all())->toBe(['inertia', 'laravel'])
        ->and(PostTag::query()->count())->toBe(2)
        ->and($post->getFirstMediaUrl('cover'))->not->toBe('');
});

test('a draft stays without a publication date', function () {
    $this->actingAs(User::factory()->create())->post(route('admin.posts.store'), [
        'title' => ['fr' => 'Brouillon'],
        'slug' => 'brouillon',
        'body' => ['fr' => '<p>En cours</p>'],
        'status' => 'draft',
    ])->assertSessionHasNoErrors();

    expect(Post::query()->firstOrFail())
        ->status->toBe(PublicationStatus::Draft)
        ->published_at->toBeNull();
});

test('the french title and body are required', function () {
    $this->actingAs(User::factory()->create())->post(route('admin.posts.store'), [
        'slug' => 'vide',
        'status' => 'draft',
    ])->assertSessionHasErrors(['title.fr', 'body.fr']);
});

test('a post can be updated and moved to the trash', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($user)->put(route('admin.posts.update', $post), [
        'title' => ['fr' => 'Nouveau titre'],
        'slug' => $post->slug,
        'body' => ['fr' => '<p>Nouveau contenu</p>'],
        'status' => 'published',
    ])->assertSessionHasNoErrors();

    expect($post->fresh()->getTranslation('title', 'fr'))->toBe('Nouveau titre');

    $this->actingAs($user)->delete(route('admin.posts.destroy', $post))->assertRedirect(route('admin.posts.index'));

    expect($post->fresh()->trashed())->toBeTrue();
});

test('an image dropped in the editor is stored and its url returned', function () {
    Storage::fake('public');

    $response = $this->actingAs(User::factory()->create())
        ->post(route('admin.posts.images.store'), ['image' => UploadedFile::fake()->image('photo.png')])
        ->assertCreated();

    expect($response->json('url'))->toContain('/blog/');
    expect(Storage::disk('public')->allFiles('blog'))->toHaveCount(1);
});
