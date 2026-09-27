<?php

use App\Models\JobProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('the short hero title cannot exceed 15 characters', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.job-profiles.store'), [
        'key' => 'devops',
        'label' => ['fr' => 'Ingénieur DevOps et infrastructure', 'en' => 'DevOps and infrastructure engineer'],
        'hero_title' => ['fr' => 'Ingénieur DevOps qui', 'en' => 'DevOps Engineer who'],
        'description' => ['fr' => 'Description', 'en' => 'Description'],
        'cv_description' => ['fr' => 'CV', 'en' => 'CV'],
    ]);

    $response->assertSessionHasErrors(['hero_title.fr', 'hero_title.en']);
});

test('a CV PDF can be uploaded per language for a job profile, replaced and removed', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $jobProfile = JobProfile::factory()->create();
    $payload = [
        'key' => $jobProfile->key,
        'label' => $jobProfile->getTranslations('label'),
        'description' => $jobProfile->getTranslations('description'),
        'cv_description' => $jobProfile->getTranslations('cv_description'),
    ];

    $this->actingAs($user)->put(route('admin.job-profiles.update', $jobProfile), $payload + [
        'cv_file_fr' => UploadedFile::fake()->createWithContent('cv-fr.pdf', '%PDF-1.4'),
        'cv_file_en' => UploadedFile::fake()->createWithContent('cv-en.pdf', '%PDF-1.4'),
    ])->assertSessionHasNoErrors();

    expect($jobProfile->fresh()->getFirstMedia('cv_file_fr')?->file_name)->toBe('cv-fr.pdf')
        ->and($jobProfile->fresh()->getFirstMedia('cv_file_en')?->file_name)->toBe('cv-en.pdf');

    $this->actingAs($user)->put(route('admin.job-profiles.update', $jobProfile), $payload + [
        'cv_file_fr' => UploadedFile::fake()->createWithContent('nouveau.pdf', '%PDF-1.4'),
    ]);

    expect($jobProfile->fresh()->getMedia('cv_file_fr'))->toHaveCount(1)
        ->and($jobProfile->fresh()->getFirstMedia('cv_file_fr')?->file_name)->toBe('nouveau.pdf');

    $this->actingAs($user)->delete(route('admin.job-profiles.cv.destroy', [$jobProfile, 'fr']))
        ->assertRedirect(route('admin.job-profiles.edit', $jobProfile));

    expect($jobProfile->fresh()->getFirstMedia('cv_file_fr'))->toBeNull()
        ->and($jobProfile->fresh()->getFirstMedia('cv_file_en'))->not->toBeNull();
});

test('only PDF files are accepted as a job profile CV', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $jobProfile = JobProfile::factory()->create();

    $this->actingAs($user)->put(route('admin.job-profiles.update', $jobProfile), [
        'key' => $jobProfile->key,
        'label' => $jobProfile->getTranslations('label'),
        'description' => $jobProfile->getTranslations('description'),
        'cv_description' => $jobProfile->getTranslations('cv_description'),
        'cv_file_fr' => UploadedFile::fake()->create('cv.docx', 100, 'application/msword'),
    ])->assertSessionHasErrors('cv_file_fr');
});
