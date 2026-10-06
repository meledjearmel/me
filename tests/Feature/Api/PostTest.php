<?php

use App\Ai\Agents\PostWriter;
use App\Enums\ProjectStatus;
use App\Jobs\TranslatePostTag;
use App\Models\Post;
use App\Models\PostShare;
use App\Models\Profile;
use App\Models\Project;
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
    Sanctum::actingAs($user = User::factory()->create());

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

    $previewUrl = $this->getJson(route('api.v1.posts.show', $id))->json('preview_url');
    Profile::factory()->create();
    auth()->forgetGuards();
    $this->get($previewUrl)->assertOk();
    Sanctum::actingAs($user);

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

test('the mentionable items are searched through the API', function () {
    Sanctum::actingAs(User::factory()->create());
    $project = Project::factory()->create(['title' => ['fr' => 'App Station', 'en' => 'App Station'], 'status' => ProjectStatus::Published]);
    Project::factory()->create(['title' => ['fr' => 'App cachée', 'en' => 'App cachée'], 'status' => ProjectStatus::Archived]);
    Post::factory()->draft()->create(['title' => ['fr' => 'Brouillon app']]);

    $this->getJson(route('api.v1.posts.mentions', ['q' => 'app']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0', ['kind' => 'project', 'id' => $project->id, 'label' => 'App Station', 'hint' => $project->getTranslation('tagline', 'fr') ?: null]);
});

test('the mention suggestions mix the kinds so many projects do not hide an article', function () {
    Sanctum::actingAs(User::factory()->create());
    Project::factory()->count(10)->create(['status' => ProjectStatus::Published]);
    $post = Post::factory()->create();

    $this->getJson(route('api.v1.posts.mentions'))
        ->assertOk()
        ->assertJsonCount(8, 'data')
        ->assertJsonPath('data.0.kind', 'project')
        ->assertJsonPath('data.1', fn (array $item): bool => $item['kind'] === 'post' && $item['id'] === $post->id);
});

test('a post exposes its share counts per network and in total', function () {
    Sanctum::actingAs(User::factory()->create());
    $post = Post::factory()->create();
    PostShare::factory()->for($post)->count(2)->create(['network' => 'whatsapp']);
    PostShare::factory()->for($post)->create(['network' => 'copy']);

    $this->getJson(route('api.v1.posts.show', $post))
        ->assertOk()
        ->assertJsonPath('shares.whatsapp', 2)
        ->assertJsonPath('shares.copy', 1)
        ->assertJsonPath('shares.linkedin', 0)
        ->assertJsonPath('shares_count', 3);
});
