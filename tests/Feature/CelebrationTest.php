<?php

use App\Models\Celebration;
use App\Models\Profile;

beforeEach(function () {
    Profile::factory()->create();
    Celebration::query()->delete();
});

test('public pages share a showable celebration', function () {
    $celebration = Celebration::factory()->create([
        'message' => ['fr' => 'Bravo !', 'en' => 'Well done!'],
        'congratulations_count' => 0,
        'chance_percent' => 25,
        'delay_seconds' => 4,
        'display_seconds' => 20,
        'snooze_days' => 3,
    ]);

    $this->get('/en')->assertInertia(fn ($page) => $page
        ->where('celebration.id', $celebration->id)
        ->where('celebration.message', 'Well done!')
        ->where('celebration.total', 0)
        ->where('celebration.chance', 0.25)
        ->where('celebration.delaySeconds', 4)
        ->where('celebration.displaySeconds', 20)
        ->where('celebration.snoozeDays', 3)
    );
});

test('inactive or out-of-period celebrations are never shared', function () {
    Celebration::factory()->inactive()->create();
    Celebration::factory()->create(['starts_at' => today()->addDay()]);
    Celebration::factory()->create(['ends_at' => today()->subDay()]);

    $this->get('/fr')->assertInertia(fn ($page) => $page->where('celebration', null));
});

test('congratulations are added to the celebration counter', function () {
    $celebration = Celebration::factory()->create();

    $this->postJson("/fr/celebrations/{$celebration->id}/congratulations", ['count' => 3])
        ->assertOk()
        ->assertJson(['total' => 3]);
    $this->postJson("/en/celebrations/{$celebration->id}/congratulations", ['count' => 2])
        ->assertJson(['total' => 5]);

    expect($celebration->fresh()->congratulations_count)->toBe(5);
});

test('a hidden celebration cannot be congratulated', function () {
    $celebration = Celebration::factory()->inactive()->create();

    $this->postJson("/fr/celebrations/{$celebration->id}/congratulations", ['count' => 1])->assertNotFound();
});

test('a single request cannot add more than the allowed batch', function (mixed $count) {
    $celebration = Celebration::factory()->create();

    $this->postJson("/fr/celebrations/{$celebration->id}/congratulations", ['count' => $count])->assertUnprocessable();

    expect($celebration->fresh()->congratulations_count)->toBe(0);
})->with([0, 26, 'abc', null]);
