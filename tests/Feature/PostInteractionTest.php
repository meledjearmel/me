<?php

use App\Enums\CommentStatus;
use App\Enums\PostShareNetwork;
use App\Jobs\SendPushNotification;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\PostShare;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PostReactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Profile::factory()->create();
});

test('a reader toggles a reaction and is recognised by a cookie', function () {
    $post = Post::factory()->create();

    $response = $this->postJson("/fr/blog/{$post->slug}/reactions", ['type' => 'love'])
        ->assertOk()
        ->assertJsonPath('counts.love', 1)
        ->assertJsonPath('mine', ['love'])
        ->assertCookie(PostReactions::COOKIE);

    $reader = $response->getCookie(PostReactions::COOKIE)->getValue();

    $this->withCookie(PostReactions::COOKIE, $reader)
        ->get("/fr/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page->where('reactions.counts.love', 1)->where('reactions.mine', ['love']));

    $this->withCredentials()
        ->withCookie(PostReactions::COOKIE, $reader)
        ->postJson("/fr/blog/{$post->slug}/reactions", ['type' => 'love'])
        ->assertJsonPath('counts.love', 0)
        ->assertJsonPath('mine', []);

    expect(PostReaction::query()->count())->toBe(0);
});

test('reactions send at most one push notification per post on the configured delay', function () {
    Queue::fake();
    SiteSetting::current()->update(['congratulation_notify_minutes' => 30]);
    $post = Post::factory()->create(['title' => ['fr' => 'Laravel en prod']]);

    $this->postJson("/fr/blog/{$post->slug}/reactions", ['type' => 'like']);
    $this->postJson("/fr/blog/{$post->slug}/reactions", ['type' => 'fire']);

    Queue::assertPushed(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job): bool => str_contains(serialize($job), 'Laravel en prod'));
});

test('reactions are refused on unknown types, unpublished posts or when disabled', function () {
    $post = Post::factory()->create();

    $this->postJson("/fr/blog/{$post->slug}/reactions", ['type' => 'angry'])->assertJsonValidationErrors('type');
    $this->postJson('/fr/blog/'.Post::factory()->draft()->create()->slug.'/reactions', ['type' => 'like'])->assertNotFound();

    SiteSetting::current()->update(['blog_reactions_enabled' => false]);

    $this->postJson("/fr/blog/{$post->slug}/reactions", ['type' => 'like'])->assertNotFound();
    $this->get("/fr/blog/{$post->slug}")->assertInertia(fn ($page) => $page->where('reactions', null));
});

test('a comment waits for moderation and only approved ones are shown', function () {
    Queue::fake();
    $post = Post::factory()->create();
    PostComment::factory()->for($post)->create(['author_name' => 'Awa', 'body' => 'Très utile']);
    PostComment::factory()->for($post)->rejected()->create();

    $this->post("/en/blog/{$post->slug}/comments", [
        'author_name' => 'Koffi',
        'author_email' => 'koffi@example.com',
        'body' => 'Merci pour cet article',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $comment = PostComment::query()->where('author_name', 'Koffi')->sole();
    expect($comment->status)->toBe(CommentStatus::Pending)->and($comment->locale)->toBe('en');
    Queue::assertPushed(SendPushNotification::class);

    $this->get("/fr/blog/{$post->slug}")->assertInertia(fn ($page) => $page
        ->has('comments', 1)
        ->where('comments.0.author_name', 'Awa')
        ->missing('comments.0.author_email'));
});

test('comments are refused from bots, on unpublished posts or when disabled', function () {
    $post = Post::factory()->create();
    $comment = ['author_name' => 'Bot', 'body' => 'Spam'];

    $this->post("/fr/blog/{$post->slug}/comments", [...$comment, 'website' => 'x'])->assertSessionHasErrors('website');
    $this->post('/fr/blog/'.Post::factory()->scheduled()->create()->slug.'/comments', $comment)->assertNotFound();

    SiteSetting::current()->update(['blog_comments_enabled' => false]);

    $this->post("/fr/blog/{$post->slug}/comments", $comment)->assertNotFound();
    $this->get("/fr/blog/{$post->slug}")->assertInertia(fn ($page) => $page->where('comments', null));
    expect(PostComment::query()->count())->toBe(0);
});

test('comments are moderated from the admin', function () {
    $comment = PostComment::factory()->pending()->create();
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.post-comments.index', ['status' => 'pending']))
        ->assertInertia(fn ($page) => $page->component('admin/post-comments/index')->where('comments.data.0.id', $comment->id));

    $this->patch(route('admin.post-comments.update', $comment), ['status' => 'approved'])->assertSessionHasNoErrors();
    expect($comment->refresh()->status)->toBe(CommentStatus::Approved);

    $this->delete(route('admin.post-comments.destroy', $comment));
    expect($comment->refresh()->trashed())->toBeTrue();

    $this->get(route('admin.trash.index'))->assertInertia(fn ($page) => $page->where('items.data.0.type', 'post-comments'));
});

test('comments are moderated through the API', function () {
    $comment = PostComment::factory()->pending()->create();

    $this->getJson(route('api.v1.post-comments.index'))->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.posts.show', $comment->post))->assertJsonPath('pending_comments_count', 1);
    $this->getJson(route('api.v1.post-comments.index', ['status' => 'pending']))
        ->assertJsonPath('data.0.id', $comment->id)
        ->assertJsonPath('data.0.post.slug', $comment->post->slug);

    $this->putJson(route('api.v1.post-comments.update', $comment), ['status' => 'rejected'])
        ->assertOk()
        ->assertJsonPath('status', 'rejected');

    $this->deleteJson(route('api.v1.post-comments.destroy', $comment))->assertNoContent();
});

test('reactions and comments can be switched off from the admin and the API', function () {
    $this->actingAs(User::factory()->create())
        ->patch(route('admin.site-settings.update'), ['blog_reactions_enabled' => '0', 'blog_comments_enabled' => '0'])
        ->assertSessionHasNoErrors();

    expect(SiteSetting::current())
        ->blog_reactions_enabled->toBeFalse()
        ->blog_comments_enabled->toBeFalse();

    Sanctum::actingAs(User::factory()->create());

    $this->patchJson(route('api.v1.site-settings.update'), ['blog_comments_enabled' => true])
        ->assertJsonPath('blog_comments_enabled', true)
        ->assertJsonPath('blog_reactions_enabled', false);
});

test('the API gives the reaction counts of a post', function () {
    $post = Post::factory()->create();
    PostReaction::factory()->for($post)->count(2)->create(['type' => 'fire']);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.posts.show', $post))
        ->assertJsonPath('reactions', ['like' => 0, 'love' => 0, 'fire' => 2, 'idea' => 0, 'think' => 0]);
});

test('the admin page of a post shows its reads, reactions, shares and comments', function () {
    $post = Post::factory()->draft()->create(['views_count' => 12]);
    PostReaction::factory()->for($post)->count(3)->create(['type' => 'love']);
    PostShare::factory()->for($post)->count(2)->create(['network' => 'whatsapp']);
    PostComment::factory()->for($post)->pending()->create(['author_name' => 'Awa']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.posts.show', $post))
        ->assertInertia(fn ($page) => $page
            ->component('admin/posts/show')
            ->where('post.views_count', 12)
            ->where('post.is_live', false)
            ->where('reactions.love', 3)
            ->where('shares.whatsapp', 2)
            ->where('comments.0.author_name', 'Awa')
            ->has('previewUrl'));
});

test('the dashboard reports the blog engagement and the comments to moderate', function () {
    $post = Post::factory()->create(['views_count' => 40]);
    PostReaction::factory()->for($post)->count(2)->create(['type' => 'fire']);
    PostReaction::factory()->for($post)->create(['type' => 'like', 'created_at' => now()->subDays(60)]);
    PostComment::factory()->for($post)->pending()->create();
    PostComment::factory()->for($post)->create();
    PostShare::factory()->for($post)->count(3)->create(['network' => 'linkedin']);
    PostShare::factory()->for($post)->create(['network' => 'copy', 'created_at' => now()->subDays(60)]);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.dashboard'))
        ->assertJsonPath('todo.comments', 1)
        ->assertJsonPath('blog.views_total', 40)
        ->assertJsonPath('blog.reactions.total', 3)
        ->assertJsonPath('blog.reactions.period', 2)
        ->assertJsonPath('blog.reactions.by_type.2', ['label' => 'fire', 'count' => 2])
        ->assertJsonPath('blog.shares.total', 4)
        ->assertJsonPath('blog.shares.period', 3)
        ->assertJsonPath('blog.shares.by_network.0', ['label' => 'linkedin', 'count' => 3])
        ->assertJsonPath('blog.comments.pending', 1)
        ->assertJsonPath('blog.comments.approved', 1)
        ->assertJsonPath('blog.top_posts.0', [
            'id' => $post->id,
            'title' => $post->getTranslation('title', 'fr'),
            'url' => "/fr/blog/{$post->slug}",
            'views' => 40,
            'reactions' => 2,
            'shares' => 3,
            'comments' => 2,
        ]);

    $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('blog.reactions.period', 2)->where('blog.shares.period', 3));
});

test('a share is counted and shown on the post', function () {
    $post = Post::factory()->create();
    $browser = ['User-Agent' => 'Mozilla/5.0'];

    $this->withHeaders($browser)->postJson("/fr/blog/{$post->slug}/shares", ['network' => 'linkedin'])
        ->assertOk()
        ->assertJsonPath('total', 1);
    $this->withHeaders($browser)->postJson("/fr/blog/{$post->slug}/shares", ['network' => 'copy'])
        ->assertJsonPath('total', 2);

    expect($post->shareCounts())->toMatchArray(['linkedin' => 1, 'copy' => 1, 'x' => 0]);

    $this->get("/fr/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page->where('sharesCount', 2));
});

test('a session counts only one share per network within half an hour', function () {
    $post = Post::factory()->create();
    $request = Request::create("/fr/blog/{$post->slug}/shares", 'POST', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0']);
    $request->setLaravelSession(app('session.store'));

    $post->recordShare($request, PostShareNetwork::X);
    $post->recordShare($request, PostShareNetwork::X);
    $post->recordShare($request, PostShareNetwork::Email);
    $this->travel(31)->minutes();
    $post->recordShare($request, PostShareNetwork::X);

    expect($post->shareCounts())->toMatchArray(['x' => 2, 'email' => 1]);
});

test('shares are refused on unknown networks or unpublished posts, and ignored for robots', function () {
    $post = Post::factory()->create();

    $this->postJson("/fr/blog/{$post->slug}/shares", ['network' => 'myspace'])->assertJsonValidationErrors('network');
    $this->postJson('/fr/blog/'.Post::factory()->draft()->create()->slug.'/shares', ['network' => 'x'])->assertNotFound();
    $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])
        ->postJson("/fr/blog/{$post->slug}/shares", ['network' => 'x'])
        ->assertJsonPath('total', 0);
});
