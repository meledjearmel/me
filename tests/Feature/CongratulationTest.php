<?php

use App\Models\Counter;
use App\Models\Profile;

beforeEach(function () {
    Profile::factory()->create();
});

test('the about page exposes the congratulations total', function () {
    Counter::add(Counter::CONGRATULATIONS, 7);

    $this->get('/fr/about')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('congratulations', 7));
});

test('the about page starts at zero congratulations', function () {
    $this->get('/fr/about')
        ->assertInertia(fn ($page) => $page->where('congratulations', 0));
});

test('congratulations are added to the shared total', function () {
    $this->postJson('/fr/congratulations', ['count' => 3])->assertOk()->assertJson(['total' => 3]);
    $this->postJson('/en/congratulations', ['count' => 4])->assertOk()->assertJson(['total' => 7]);

    expect(Counter::total(Counter::CONGRATULATIONS))->toBe(7);
});

test('a single request cannot add more than the allowed batch', function (mixed $count) {
    $this->postJson('/fr/congratulations', ['count' => $count])->assertUnprocessable();

    expect(Counter::total(Counter::CONGRATULATIONS))->toBe(0);
})->with([0, -5, 26, 'abc', null]);

test('congratulations are rate limited', function () {
    foreach (range(1, 60) as $ignored) {
        $this->postJson('/fr/congratulations', ['count' => 1])->assertOk();
    }

    $this->postJson('/fr/congratulations', ['count' => 1])->assertTooManyRequests();
});
