<?php

use App\Mail\NewPostMail;
use App\Mail\NewsletterConfirmationMail;
use App\Models\Post;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Profile::factory()->create();
    Mail::fake();
});

test('subscribing sends a confirmation email without activating the subscription', function () {
    $this->post('/en/newsletter', ['email' => 'Jane@Example.com'])->assertRedirect();

    $subscriber = Subscriber::query()->sole();
    expect($subscriber->email)->toBe('jane@example.com')
        ->and($subscriber->locale)->toBe('en')
        ->and($subscriber->isActive())->toBeFalse();
    Mail::assertQueued(NewsletterConfirmationMail::class, fn (NewsletterConfirmationMail $mail) => $mail->hasTo('jane@example.com'));
});

test('subscribing again with an active address sends nothing', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'jane@example.com']);

    $this->post('/fr/newsletter', ['email' => 'jane@example.com'])->assertRedirect();

    Mail::assertNothingQueued();
    expect(Subscriber::query()->sole()->isActive())->toBeTrue();
});

test('an unsubscribed address can subscribe again after a new confirmation', function () {
    $subscriber = Subscriber::factory()->unsubscribed()->create(['email' => 'jane@example.com']);

    $this->post('/fr/newsletter', ['email' => 'jane@example.com'])->assertRedirect();

    $subscriber->refresh();
    expect($subscriber->unsubscribed_at)->toBeNull()->and($subscriber->confirmed_at)->toBeNull();
    Mail::assertQueued(NewsletterConfirmationMail::class);
});

test('subscribing requires a valid email and rejects the honeypot', function () {
    $this->post('/fr/newsletter', ['email' => 'not-an-email'])->assertSessionHasErrors('email');
    $this->post('/fr/newsletter', ['email' => 'bot@example.com', 'website' => 'x'])->assertSessionHasErrors('website');

    expect(Subscriber::query()->count())->toBe(0);
});

test('subscribing is unavailable when the blog is disabled', function () {
    SiteSetting::current()->update(['blog_enabled' => false]);

    $this->post('/fr/newsletter', ['email' => 'jane@example.com'])->assertNotFound();
});

test('the confirmation link activates the subscription', function () {
    $subscriber = Subscriber::factory()->create();

    $this->get("/fr/newsletter/{$subscriber->token}/confirm")->assertRedirect('/fr/blog');

    expect($subscriber->refresh()->isActive())->toBeTrue();
});

test('the confirmation link does not resubscribe an unsubscribed address', function () {
    $subscriber = Subscriber::factory()->unsubscribed()->create();

    $this->get("/fr/newsletter/{$subscriber->token}/confirm");

    expect($subscriber->refresh()->isActive())->toBeFalse();
});

test('an unknown token is not found', function () {
    $this->get('/fr/newsletter/unknown/confirm')->assertNotFound();
    $this->get('/fr/newsletter/unknown/unsubscribe')->assertNotFound();
});

test('the unsubscribe page shows the subscription without changing it', function () {
    $subscriber = Subscriber::factory()->confirmed()->create();

    $this->get("/fr/newsletter/{$subscriber->token}/unsubscribe")->assertOk()->assertInertia(fn ($page) => $page
        ->component('public/newsletter-unsubscribe')
        ->where('unsubscribed', false));

    expect($subscriber->refresh()->isActive())->toBeTrue();
});

test('a one-click unsubscribe from the mail client works without a CSRF token', function () {
    $subscriber = Subscriber::factory()->confirmed()->create();

    $this->post("/fr/newsletter/{$subscriber->token}/unsubscribe", ['List-Unsubscribe' => 'One-Click'])->assertNoContent();

    expect($subscriber->refresh()->isActive())->toBeFalse();
});

test('a new post is sent once to active subscribers only', function () {
    $active = Subscriber::factory()->confirmed()->create(['locale' => 'en']);
    Subscriber::factory()->create();
    Subscriber::factory()->unsubscribed()->create();
    $post = Post::factory()->create();
    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();

    $this->artisan('newsletter:send')->assertSuccessful();
    $this->artisan('newsletter:send')->assertSuccessful();

    Mail::assertQueuedCount(1);
    Mail::assertQueued(NewPostMail::class, fn (NewPostMail $mail) => $mail->hasTo($active->email)
        && $mail->post->is($post)
        && $mail->headers()->text['List-Unsubscribe'] === "<{$active->unsubscribeUrl()}>");
});

test('no post is sent while the blog is disabled', function () {
    Subscriber::factory()->confirmed()->create();
    $post = Post::factory()->create();
    SiteSetting::current()->update(['blog_enabled' => false]);

    $this->artisan('newsletter:send')->assertSuccessful();

    Mail::assertNothingQueued();
    expect($post->refresh()->newsletter_sent_at)->toBeNull();
});
