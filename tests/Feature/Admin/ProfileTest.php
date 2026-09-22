<?php

use App\Models\Profile;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.profile.edit'))->assertRedirect(route('login'));
});

test('authenticated users can update the profile', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create();

    $response = $this->actingAs($user)->patch(route('admin.profile.update'), [
        'name' => 'Nouveau nom',
        'headline' => $profile->getTranslations('headline'),
        'bio_short' => $profile->getTranslations('bio_short'),
        'bio_full' => $profile->getTranslations('bio_full'),
        'email' => $profile->email,
    ]);

    $response->assertRedirect(route('admin.profile.edit'));
    expect($profile->fresh()->name)->toBe('Nouveau nom');
});

test('updating the profile requires the mandatory fields', function () {
    $user = User::factory()->create();
    Profile::factory()->create();

    $response = $this->actingAs($user)->patch(route('admin.profile.update'), []);

    $response->assertSessionHasErrors(['name', 'email']);
});
