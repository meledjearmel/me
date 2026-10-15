<?php

use App\Models\AppRelease;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('local');
});

test('the app gets the latest published version with a signed download url', function () {
    Sanctum::actingAs(User::factory()->create());
    AppRelease::factory()->published()->create(['build_number' => 4, 'version_name' => '1.1.0']);
    AppRelease::factory()->uploaded()->create(['build_number' => 5]);

    $response = $this->getJson(route('api.v1.app.latest'))
        ->assertOk()
        ->assertJsonPath('data.version_name', '1.1.0')
        ->assertJsonPath('data.build_number', 4);

    expect($response->json('data.download_url'))->toContain('signature=');
});

test('latest answers 204 when nothing is published, and requires authentication', function () {
    $this->getJson(route('api.v1.app.latest'))->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());
    AppRelease::factory()->uploaded()->create();

    $this->getJson(route('api.v1.app.latest'))->assertNoContent();
});

test('the apk is downloaded only through a signed url of a published build', function () {
    $published = AppRelease::factory()->published()->create();
    $unpublished = AppRelease::factory()->uploaded()->create();
    Storage::disk('local')->put($published->file_path, 'apk');
    Storage::disk('local')->put($unpublished->file_path, 'apk');

    $this->get($published->downloadUrl())->assertOk()->assertDownload($published->fileName());
    $this->get(route('api.v1.app-releases.download', $published))->assertForbidden();
    $this->get($unpublished->downloadUrl())->assertNotFound();
});
