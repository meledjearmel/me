<?php

use App\Models\Subscriber;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.subscribers.index'))->assertRedirect(route('login'));
});

test('the list can be filtered by subscription status', function (string $status, string $expectedEmail) {
    Subscriber::factory()->confirmed()->create(['email' => 'active@example.com']);
    Subscriber::factory()->create(['email' => 'pending@example.com']);
    Subscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.subscribers.index', ['status' => $status]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/subscribers/index')
            ->has('subscribers.data', 1)
            ->where('subscribers.data.0.email', $expectedEmail)
            ->where('summary', ['active' => 1, 'pending' => 1, 'unsubscribed' => 1]));
})->with([
    'active' => ['active', 'active@example.com'],
    'pending' => ['pending', 'pending@example.com'],
    'unsubscribed' => ['unsubscribed', 'gone@example.com'],
]);

test('the list does not expose subscription tokens', function () {
    Subscriber::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.subscribers.index'))
        ->assertInertia(fn ($page) => $page->missing('subscribers.data.0.token'));
});

test('a subscriber can be deleted', function () {
    $subscriber = Subscriber::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.subscribers.destroy', $subscriber))
        ->assertRedirect(route('admin.subscribers.index'));

    $this->assertModelMissing($subscriber);
});
