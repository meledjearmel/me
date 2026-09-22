<?php

use App\Models\JobProfile;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.job-profiles.index'))->assertRedirect(route('login'));
});

test('authenticated users can create a job profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.job-profiles.store'), [
        'key' => 'devops',
        'label' => ['fr' => 'DevOps', 'en' => 'DevOps'],
        'description' => ['fr' => 'Description', 'en' => 'Description'],
        'cv_description' => ['fr' => 'CV', 'en' => 'CV'],
        'sort_order' => 1,
    ]);

    $response->assertRedirect(route('admin.job-profiles.index'));
    $this->assertDatabaseHas('job_profiles', ['key' => 'devops']);
});

test('authenticated users can update a job profile', function () {
    $user = User::factory()->create();
    $jobProfile = JobProfile::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.job-profiles.update', $jobProfile), [
        'key' => $jobProfile->key,
        'label' => ['fr' => 'Nouveau', 'en' => 'New'],
        'description' => ['fr' => 'Desc', 'en' => 'Desc'],
        'cv_description' => ['fr' => 'CV', 'en' => 'CV'],
        'sort_order' => 2,
    ]);

    $response->assertRedirect(route('admin.job-profiles.index'));
    expect($jobProfile->fresh()->getTranslation('label', 'fr'))->toBe('Nouveau');
});

test('authenticated users can delete a job profile', function () {
    $user = User::factory()->create();
    $jobProfile = JobProfile::factory()->create();

    $this->actingAs($user)->delete(route('admin.job-profiles.destroy', $jobProfile))
        ->assertRedirect(route('admin.job-profiles.index'));

    $this->assertSoftDeleted($jobProfile);
});

test('creating a job profile requires the mandatory fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.job-profiles.store'), []);

    $response->assertSessionHasErrors([
        'key', 'label.fr', 'label.en', 'description.fr', 'description.en', 'cv_description.fr', 'cv_description.en',
    ]);
});
