<?php

use App\Models\Celebration;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.celebrations.index'))->assertRedirect(route('login'));
});

test('authenticated users can create and update a celebration', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.celebrations.store'), [
        'message' => ['fr' => 'Nouveau diplôme !', 'en' => 'New degree!'],
        'button_label' => ['fr' => 'Bravo', 'en' => 'Cheers'],
        'congratulated_for' => 'votre nouveau diplôme',
        'is_active' => '1',
        'weight' => 2,
        'chance_percent' => 50,
        'delay_seconds' => 5,
        'display_seconds' => 15,
        'snooze_days' => 7,
    ])->assertRedirect(route('admin.celebrations.index'));

    $celebration = Celebration::query()->latest('id')->firstOrFail();

    $this->actingAs($user)->put(route('admin.celebrations.update', $celebration), [
        'message' => ['fr' => 'Diplôme obtenu !', 'en' => 'Degree earned!'],
        'button_label' => ['fr' => 'Bravo', 'en' => 'Cheers'],
        'congratulated_for' => 'votre master',
        'is_active' => '0',
        'starts_at' => '2026-10-01',
        'ends_at' => '2026-12-31',
        'weight' => 1,
        'chance_percent' => 100,
        'delay_seconds' => 0,
        'display_seconds' => 30,
        'snooze_days' => 0,
    ])->assertRedirect(route('admin.celebrations.index'));

    $celebration->refresh();

    expect($celebration->getTranslation('message', 'fr'))->toBe('Diplôme obtenu !')
        ->and($celebration->congratulated_for)->toBe('votre master')
        ->and($celebration->is_active)->toBeFalse()
        ->and($celebration->ends_at->toDateString())->toBe('2026-12-31')
        ->and($celebration->chance_percent)->toBe(100)
        ->and($celebration->delay_seconds)->toBe(0)
        ->and($celebration->display_seconds)->toBe(30)
        ->and($celebration->snooze_days)->toBe(0);
});

test('a celebration requires its texts and a coherent period', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.celebrations.store'), [
            'starts_at' => '2026-10-10',
            'ends_at' => '2026-10-01',
            'chance_percent' => 0,
        ])
        ->assertSessionHasErrors(['message.fr', 'message.en', 'button_label.fr', 'button_label.en', 'congratulated_for', 'ends_at', 'chance_percent', 'delay_seconds', 'display_seconds', 'snooze_days']);
});

test('authenticated users can delete a celebration', function () {
    $celebration = Celebration::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.celebrations.destroy', $celebration))
        ->assertRedirect(route('admin.celebrations.index'));

    $this->assertModelMissing($celebration);
});
