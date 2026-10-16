<?php

use App\Models\Counter;
use App\Models\Distinction;
use App\Models\Profile;

beforeEach(function () {
    Profile::factory()->create();
});

test('the about page exposes the congratulations of each distinction', function () {
    Distinction::factory()->create(['congratulations_count' => 7]);

    $this->get('/fr/about')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('distinctions.0.congratulations', 7));
});

test('a new distinction starts at zero congratulations', function () {
    Distinction::factory()->create();

    $this->get('/fr/about')
        ->assertInertia(fn ($page) => $page->where('distinctions.0.congratulations', 0));
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
