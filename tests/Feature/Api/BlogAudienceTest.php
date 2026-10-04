<?php

use App\Enums\PublicationStatus;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\UsesItem;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
});

test('guests cannot reach the subscribers, tags or uses endpoints', function () {
    $this->app['auth']->forgetGuards();

    $this->getJson(route('api.v1.subscribers.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.post-tags.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.uses-items.index'))->assertUnauthorized();
});

test('subscribers are listed with their status, filterable, with totals and never their token', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'active@example.com']);
    Subscriber::factory()->create(['email' => 'pending@example.com']);
    Subscriber::factory()->unsubscribed()->create();

    $this->getJson(route('api.v1.subscribers.index', ['status' => 'pending']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'pending@example.com')
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonMissingPath('data.0.token')
        ->assertJsonPath('summary', ['active' => 1, 'pending' => 1, 'unsubscribed' => 1]);
});

test('a subscriber can be deleted', function () {
    $subscriber = Subscriber::factory()->create();

    $this->deleteJson(route('api.v1.subscribers.destroy', $subscriber))->assertNoContent();

    $this->assertModelMissing($subscriber);
});

test('a blog tag can be renamed in both languages and deleted, leaving its posts', function () {
    $tag = PostTag::factory()->create(['slug' => 'laravel']);
    $post = Post::factory()->hasAttached($tag, [], 'tags')->create();

    $this->getJson(route('api.v1.post-tags.index'))->assertOk()->assertJsonPath('data.0.posts_count', 1);

    $this->putJson(route('api.v1.post-tags.update', $tag), ['name' => ['fr' => 'Laravel FR', 'en' => 'Laravel EN']])
        ->assertOk()
        ->assertJsonPath('name.en', 'Laravel EN')
        ->assertJsonPath('slug', 'laravel');

    $this->putJson(route('api.v1.post-tags.update', $tag), ['name' => ['fr' => 'Sans anglais']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name.en');

    $this->deleteJson(route('api.v1.post-tags.destroy', $tag))->assertNoContent();

    $this->assertModelMissing($tag);
    expect($post->refresh()->tags)->toBeEmpty();
});

test('a uses item can be created, published or drafted, then sent to the trash', function () {
    $response = $this->postJson(route('api.v1.uses-items.store'), [
        'category' => 'hardware',
        'name' => 'MacBook Pro',
        'description' => ['fr' => 'Mon ordinateur', 'en' => 'My computer'],
        'sort_order' => 1,
    ])->assertCreated()
        ->assertJsonPath('status', 'published')
        ->assertJsonPath('description.en', 'My computer');

    $item = UsesItem::query()->findOrFail($response->json('id'));

    $this->putJson(route('api.v1.uses-items.update', $item), [
        'category' => 'hardware',
        'name' => 'MacBook Pro',
        'status' => 'draft',
    ])->assertOk()->assertJsonPath('status', 'draft');

    $this->getJson(route('api.v1.uses-items.index', ['status' => 'draft']))->assertJsonCount(1, 'data');

    $this->deleteJson(route('api.v1.uses-items.destroy', $item))->assertNoContent();
    $this->getJson(route('api.v1.trash.index', ['type' => 'uses-items']))->assertJsonPath('data.0.title', 'MacBook Pro');

    $this->patchJson(route('api.v1.trash.restore', ['type' => 'uses-items', 'id' => $item->id]))->assertSuccessful();
    expect($item->refresh()->trashed())->toBeFalse()
        ->and($item->status)->toBe(PublicationStatus::Draft);
});
