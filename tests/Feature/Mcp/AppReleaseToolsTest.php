<?php

use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\ListAppReleasesTool;
use App\Mcp\Tools\PrepareAppReleaseTool;
use App\Mcp\Tools\PublishAppReleaseTool;
use App\Models\AppRelease;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->server = PortfolioServer::actingAs(User::factory()->create());
});

/** Archive minimale reconnue comme APK (manifeste et code compilé). */
function fakeApk(string $content = 'dex'): string
{
    $path = tempnam(sys_get_temp_dir(), 'apk');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('AndroidManifest.xml', 'manifest');
    $zip->addFromString('classes.dex', $content);
    $zip->close();

    return $path;
}

test('prepare-app-release registers a build and gives a signed upload command', function () {
    $this->server->tool(PrepareAppReleaseTool::class, ['version_name' => '1.4.0', 'build_number' => 12, 'notes' => 'Corrections'])
        ->assertOk()
        ->assertSee(['upload_url', 'curl --fail-with-body -T build/app/outputs/flutter-apk/app-release.apk', 'signature=']);

    expect(AppRelease::query()->sole())
        ->version_name->toBe('1.4.0')
        ->build_number->toBe(12)
        ->published_at->toBeNull();
});

test('prepare-app-release refuses a build number that does not increase', function () {
    AppRelease::factory()->create(['build_number' => 12]);
    AppRelease::factory()->published()->create(['build_number' => 11]);

    $this->server->tool(PrepareAppReleaseTool::class, ['version_name' => '1.3.0', 'build_number' => 10])
        ->assertHasErrors(['trop petit']);
    $this->server->tool(PrepareAppReleaseTool::class, ['version_name' => '1.3.0', 'build_number' => 11])
        ->assertHasErrors(['déjà publié']);
    $this->server->tool(PrepareAppReleaseTool::class, ['version_name' => '1.4.1', 'build_number' => 12])
        ->assertOk();

    expect(AppRelease::query()->count())->toBe(2)
        ->and(AppRelease::query()->firstWhere('build_number', 12)->version_name)->toBe('1.4.1');
});

test('the signed url receives the apk and checks it', function () {
    $release = AppRelease::factory()->create(['build_number' => 3]);
    $apk = fakeApk();

    $this->call('PUT', $release->uploadUrl(), content: file_get_contents($apk))
        ->assertOk()
        ->assertJsonPath('data.has_file', true)
        ->assertJsonPath('data.sha256', hash_file('sha256', $apk));

    Storage::disk('local')->assertExists($release->refresh()->file_path);

    $this->call('PUT', $release->uploadUrl(), content: 'not a zip')->assertUnprocessable();
    $this->call('PUT', route('api.v1.app-releases.upload', $release), content: file_get_contents($apk))->assertForbidden();
});

test('the upload is refused when the announced sha256 differs', function () {
    $release = AppRelease::factory()->create(['expected_sha256' => str_repeat('b', 64)]);

    $this->call('PUT', $release->uploadUrl(), content: file_get_contents(fakeApk()))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Fichier refusé : son SHA-256 ne correspond pas à celui annoncé.');

    expect($release->refresh()->hasFile())->toBeFalse();
});

test('publish-app-release publishes only an uploaded build that is not older', function () {
    $missingFile = AppRelease::factory()->create(['build_number' => 20]);
    $uploaded = AppRelease::factory()->uploaded()->create(['build_number' => 21]);
    $older = AppRelease::factory()->uploaded()->create(['build_number' => 5]);

    $this->server->tool(PublishAppReleaseTool::class, ['id' => $missingFile->id])->assertHasErrors(['pas encore été envoyé']);
    $this->server->tool(PublishAppReleaseTool::class, ['id' => $uploaded->id])->assertOk()->assertSee('publiée');
    $this->server->tool(PublishAppReleaseTool::class, ['id' => $older->id])->assertHasErrors(['plus récente']);

    expect(AppRelease::latestPublished()->is($uploaded))->toBeTrue();
});

test('list-app-releases gives the next build number', function () {
    AppRelease::factory()->published()->create(['build_number' => 7, 'version_name' => '1.2.0']);

    $this->server->tool(ListAppReleasesTool::class)
        ->assertOk()
        ->assertSee(['"next_build_number":8', '1.2.0']);
});
