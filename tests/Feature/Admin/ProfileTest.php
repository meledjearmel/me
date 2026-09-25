<?php

use App\Models\JobProfile;
use App\Models\Profile;
use App\Models\User;
use App\Services\CvGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('the site photo and the CV photo are two separate uploads', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $profile = Profile::factory()->create();

    $this->actingAs($user)->patch(route('admin.profile.update'), [
        'name' => $profile->name,
        'headline' => $profile->getTranslations('headline'),
        'bio_short' => $profile->getTranslations('bio_short'),
        'bio_full' => $profile->getTranslations('bio_full'),
        'email' => $profile->email,
        'photo' => UploadedFile::fake()->image('site.jpg'),
        'cv_photo' => UploadedFile::fake()->image('cv.jpg'),
    ])->assertRedirect(route('admin.profile.edit'));

    $profile = $profile->fresh();

    expect($profile->getFirstMedia('photo')?->file_name)->toBe('site.jpg')
        ->and($profile->getFirstMedia('cv_photo')?->file_name)->toBe('cv.jpg');
});

test('uploading only the CV photo keeps the site photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $profile = Profile::factory()->create();
    $profile->addMedia(UploadedFile::fake()->image('site.jpg'))->toMediaCollection('photo');

    $this->actingAs($user)->patch(route('admin.profile.update'), [
        'name' => $profile->name,
        'headline' => $profile->getTranslations('headline'),
        'bio_short' => $profile->getTranslations('bio_short'),
        'bio_full' => $profile->getTranslations('bio_full'),
        'email' => $profile->email,
        'cv_photo' => UploadedFile::fake()->image('cv.jpg'),
    ]);

    expect($profile->fresh()->getFirstMedia('photo')?->file_name)->toBe('site.jpg');
});

test('the CV uses the CV photo, never the site photo', function () {
    Storage::fake('public');

    $profile = Profile::factory()->create();
    $jobProfile = JobProfile::factory()->create();
    $generator = app(CvGenerator::class);

    // Seule la photo du site existe : le CV n'affiche pas de photo.
    $profile->addMedia(UploadedFile::fake()->image('site.jpg'))->toMediaCollection('photo');
    expect($generator->data($jobProfile, 'fr')['photo'])->toBeNull();

    $profile->addMedia(UploadedFile::fake()->image('cv.jpg'))->toMediaCollection('cv_photo');
    expect($generator->data($jobProfile, 'fr')['photo'])->toStartWith('data:image/');
});

test('the CV shows the last name and first names separately, and falls back to the site name', function () {
    $profile = Profile::factory()->create(['name' => 'Armel Meledje', 'cv_last_name' => null, 'cv_first_name' => null]);
    $jobProfile = JobProfile::factory()->create();
    $generator = app(CvGenerator::class);

    expect($generator->data($jobProfile, 'fr'))->toMatchArray(['first_name' => 'Armel Meledje', 'last_name' => '']);

    $profile->update(['cv_last_name' => 'MELEDJE GNAGNE', 'cv_first_name' => 'Christian Armel']);

    expect($generator->data($jobProfile, 'fr'))->toMatchArray(['first_name' => 'Christian Armel', 'last_name' => 'MELEDJE GNAGNE'])
        ->and($generator->filename($jobProfile))->toContain('Meledje-Gnagne-Christian-Armel');
});

test('the CV identity is saved from the admin and never exposed on the public site', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create();

    $this->actingAs($user)->patch(route('admin.profile.update'), [
        'name' => $profile->name,
        'cv_last_name' => 'NOM CV',
        'cv_first_name' => 'Prénoms CV',
        'headline' => $profile->getTranslations('headline'),
        'bio_short' => $profile->getTranslations('bio_short'),
        'bio_full' => $profile->getTranslations('bio_full'),
        'email' => $profile->email,
    ])->assertRedirect(route('admin.profile.edit'));

    expect($profile->fresh()->cv_last_name)->toBe('NOM CV')
        ->and($profile->fresh()->cv_first_name)->toBe('Prénoms CV');

    $this->get('/fr')->assertInertia(fn ($page) => $page->missing('profile.cv_last_name')->missing('profile.cv_first_name'));
});
