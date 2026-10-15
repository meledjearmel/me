<?php

use App\Models\AppRelease;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('guests cannot see the mobile app builds', function () {
    $this->get(route('admin.app-releases.index'))->assertRedirect(route('login'));
});

test('the admin uploads an apk, publishes it and deletes it', function () {
    $this->actingAs(User::factory()->create());

    $path = tempnam(sys_get_temp_dir(), 'apk');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('AndroidManifest.xml', 'manifest');
    $zip->addFromString('classes.dex', 'dex');
    $zip->close();

    $this->post(route('admin.app-releases.store'), [
        'version_name' => '2.0.0',
        'build_number' => 30,
        'notes' => 'Nouvelle version',
        'apk' => new UploadedFile($path, 'app-release.apk', test: true),
    ])->assertRedirect(route('admin.app-releases.index'));

    $release = AppRelease::query()->sole();
    Storage::disk('local')->assertExists($release->file_path);

    $this->post(route('admin.app-releases.publish', $release))->assertRedirect();
    expect($release->refresh()->isPublished())->toBeTrue();

    $this->get(route('admin.app-releases.index'))->assertInertia(fn ($page) => $page
        ->component('admin/app-releases/index')
        ->has('releases', 1));
    $this->get(route('admin.app-releases.create'))->assertInertia(fn ($page) => $page
        ->component('admin/app-releases/create')
        ->where('nextBuildNumber', 31));

    $this->delete(route('admin.app-releases.destroy', $release))->assertRedirect();
    Storage::disk('local')->assertMissing($release->file_path);
});

test('the admin upload rejects a file that is not an apk', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('admin.app-releases.store'), [
        'version_name' => '2.0.0',
        'build_number' => 30,
        'apk' => UploadedFile::fake()->create('app.apk', 10),
    ])->assertSessionHasErrors('apk');

    expect(AppRelease::query()->count())->toBe(0);
});
