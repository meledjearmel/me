<?php

use App\Ai\Agents\PostWriter;
use App\Jobs\TranslatePostTag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Queue::fake([TranslatePostTag::class]);
});

test('guests cannot read the posts', function () {
    $this->getJson(route('api.v1.posts.index'))->assertUnauthorized();
});

test('posts can be created, listed, updated and trashed through the API', function () {
    Sanctum::actingAs(User::factory()->create());

    $id = $this->postJson(route('api.v1.posts.store'), [
        'title' => ['fr' => 'Depuis le mobile'],
        'slug' => 'depuis-le-mobile',
        'body' => ['fr' => '<p>Écrit <em>en route</em></p><script>x</script>'],
        'status' => 'published',
        'tags' => ['Mobile'],
    ])->assertCreated()
        ->assertJsonPath('body.fr', '<p>Écrit <em>en route</em></p>')
        ->assertJsonPath('tags', ['Mobile'])
        ->assertJsonPath('is_live', true)
        ->json('id');

    $this->getJson(route('api.v1.posts.index'))->assertJsonPath('data.0.id', $id);
    $this->getJson(route('api.v1.posts.tags'))->assertExactJson(['data' => ['Mobile']]);

    $this->putJson(route('api.v1.posts.update', $id), [
        'title' => ['fr' => 'Depuis le mobile', 'en' => 'From mobile'],
        'slug' => 'depuis-le-mobile',
        'body' => ['fr' => '<p>Écrit</p>', 'en' => '<p>Written</p>'],
        'status' => 'draft',
    ])->assertOk()
        ->assertJsonPath('title.en', 'From mobile')
        ->assertJsonPath('is_live', false)
        ->assertJsonPath('tags', []);

    $this->deleteJson(route('api.v1.posts.destroy', $id))->assertNoContent();

    expect(Post::withTrashed()->find($id)->trashed())->toBeTrue();
});

test('an image for the content is uploaded through the API', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->create());

    $this->post(route('api.v1.posts.images.store'), ['image' => UploadedFile::fake()->image('a.png')], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonStructure(['url']);
});

test('the AI writes in a post through the API', function () {
    Sanctum::actingAs(User::factory()->create());
    PostWriter::fake(['<p>Suite rédigée.</p>']);

    $this->postJson(route('api.v1.ai.write-post'), [
        'instruction' => 'Continue',
        'locale' => 'fr',
        'context' => 'Début de l’article.',
    ])->assertOk()->assertExactJson(['text' => '<p>Suite rédigée.</p>']);
});
