<?php

use App\Models\Post;
use App\Models\Profile;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Profile::factory()->create(['name' => 'Armel Meledje']);
});

test('a published post gets a 1200x630 PNG share image', function () {
    $post = Post::factory()->create(['slug' => 'mon-article', 'title' => ['fr' => 'Un titre assez long pour passer sur plusieurs lignes de l’image de partage', 'en' => 'Title']]);

    $response = $this->get('/fr/blog/mon-article/share.png')->assertOk()->assertHeader('Content-Type', 'image/png');

    [$width, $height] = getimagesizefromstring($response->getFile()->getContent());
    expect([$width, $height])->toBe([1200, 630]);
});

test('the share image is drawn once, then redrawn only when the title changes', function () {
    $post = Post::factory()->create(['slug' => 'mon-article', 'title' => ['fr' => 'Premier titre', 'en' => 'First title']]);

    $this->get('/fr/blog/mon-article/share.png')->assertOk();
    $this->get('/fr/blog/mon-article/share.png')->assertOk();
    expect(Storage::disk('local')->files('share-images'))->toHaveCount(1);

    $post->update(['title' => ['fr' => 'Nouveau titre', 'en' => 'New title']]);
    $this->get('/fr/blog/mon-article/share.png')->assertOk();

    expect(Storage::disk('local')->files('share-images'))->toHaveCount(2);
});

test('there is no share image for a draft post or while the blog is disabled', function () {
    Post::factory()->draft()->create(['slug' => 'brouillon']);
    Post::factory()->create(['slug' => 'publie']);

    $this->get('/fr/blog/brouillon/share.png')->assertNotFound();

    SiteSetting::current()->update(['blog_enabled' => false]);
    $this->get('/fr/blog/publie/share.png')->assertNotFound();
});
