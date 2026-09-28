<?php

use App\Models\User;
use App\Services\TechnologyIconLibrary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->iconDirectory = sys_get_temp_dir().'/technology-icons-'.Str::random(12);

    // Bibliothèque isolée : les imports n'écrivent pas dans public/icons/tech.
    $this->app->instance(TechnologyIconLibrary::class, new TechnologyIconLibrary($this->iconDirectory));

    Http::preventStrayRequests();
});

afterEach(function () {
    File::deleteDirectory($this->iconDirectory);
});

function svg(string $body = '<path d="M0 0h1v1z" fill="currentColor"/>'): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.$body.'</svg>';
}

test('guests cannot search or import logos', function () {
    $this->getJson(route('admin.technology-icons.search', ['q' => 'laravel']))->assertUnauthorized();
    $this->postJson(route('admin.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => 'laravel'])->assertUnauthorized();
});

test('searching lists the catalog logos with a preview', function () {
    Http::fake([
        'api.iconify.design/search*' => Http::response(['icons' => ['logos:laravel', 'simple-icons:laravel', 'mdi:laravel', 'logos:Laravel Bad']]),
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->getJson(route('admin.technology-icons.search', ['q' => 'laravel']));

    $response->assertOk()->assertExactJson(['icons' => [
        [
            'id' => 'logos:laravel',
            'name' => 'laravel',
            'collection' => 'logos',
            'preview_url' => 'https://api.iconify.design/logos/laravel.svg',
        ],
        [
            'id' => 'simple-icons:laravel',
            'name' => 'laravel',
            'collection' => 'simple-icons',
            'preview_url' => 'https://api.iconify.design/simple-icons/laravel.svg',
        ],
    ]]);
    Http::assertSent(fn ($request) => $request['query'] === 'laravel' && $request['prefixes'] === 'logos,devicon,simple-icons');
});

test('searching requires a query of at least two characters', function () {
    $this->actingAs(User::factory()->create())
        ->getJson(route('admin.technology-icons.search', ['q' => 'a']))
        ->assertJsonValidationErrors('q');
});

test('searching reports an unavailable catalog', function () {
    Http::fake(['api.iconify.design/search*' => Http::response('', 500)]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('admin.technology-icons.search', ['q' => 'laravel']))
        ->assertStatus(502)
        ->assertJsonPath('message', 'Le catalogue de logos est momentanément indisponible.');
});

test('importing a colored logo saves a single file', function () {
    Http::fake(['api.iconify.design/logos/laravel.svg' => Http::response(svg('<path d="M0 0h1v1z" fill="#f00"/>'))]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => 'laravel']);

    $response->assertCreated()
        ->assertJsonPath('icon.slug', 'laravel')
        ->assertJsonPath('icon.light_url', fn ($url) => str_contains($url, '/icons/tech/laravel.svg'))
        ->assertJsonPath('icon.dark_url', fn ($url) => str_contains($url, '/icons/tech/laravel.svg'));
    expect(File::exists("{$this->iconDirectory}/laravel.svg"))->toBeTrue()
        ->and(File::exists("{$this->iconDirectory}/laravel-light.svg"))->toBeFalse();
});

test('importing a monochrome logo saves a light and a dark variant', function () {
    Http::fake(['api.iconify.design/simple-icons/github.svg' => Http::response(svg())]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'simple-icons:github', 'slug' => 'github']);

    $response->assertCreated()
        ->assertJsonPath('icon.light_url', fn ($url) => str_contains($url, '/icons/tech/github-light.svg'))
        ->assertJsonPath('icon.dark_url', fn ($url) => str_contains($url, '/icons/tech/github-dark.svg'));
    expect(File::get("{$this->iconDirectory}/github-light.svg"))->toContain('#18181b')->not->toContain('currentColor')
        ->and(File::get("{$this->iconDirectory}/github-dark.svg"))->toContain('#fafafa')->not->toContain('currentColor');
});

test('a dark variant can be added to an existing logo', function () {
    File::ensureDirectoryExists($this->iconDirectory);
    File::put("{$this->iconDirectory}/github.svg", svg('<path fill="#f00"/>'));
    Http::fake(['api.iconify.design/simple-icons/github.svg' => Http::response(svg())]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'simple-icons:github', 'slug' => 'github', 'theme' => 'dark']);

    $response->assertCreated()
        ->assertJsonPath('icon.light_url', fn ($url) => str_contains($url, '/icons/tech/github.svg'))
        ->assertJsonPath('icon.dark_url', fn ($url) => str_contains($url, '/icons/tech/github-dark.svg'));
    expect(File::get("{$this->iconDirectory}/github-dark.svg"))->toContain('#fafafa')
        ->and(File::exists("{$this->iconDirectory}/github-light.svg"))->toBeFalse()
        ->and(File::get("{$this->iconDirectory}/github.svg"))->toContain('#f00');
});

test('a colored logo imported for one theme is saved under that theme', function () {
    Http::fake(['api.iconify.design/logos/laravel.svg' => Http::response(svg('<path fill="#f00"/>'))]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => 'laravel', 'theme' => 'light'])
        ->assertCreated();

    expect(File::exists("{$this->iconDirectory}/laravel-light.svg"))->toBeTrue()
        ->and(File::exists("{$this->iconDirectory}/laravel.svg"))->toBeFalse();
});

test('importing refuses a theme variant that already exists', function () {
    File::ensureDirectoryExists($this->iconDirectory);
    File::put("{$this->iconDirectory}/github-dark.svg", svg());

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'simple-icons:github', 'slug' => 'github', 'theme' => 'dark'])
        ->assertJsonValidationErrors(['slug' => 'Ce nom de logo est déjà utilisé.']);
    Http::assertNothingSent();
});

test('importing refuses an unknown theme', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => 'laravel', 'theme' => 'sepia'])
        ->assertJsonValidationErrors('theme');
    Http::assertNothingSent();
});

test('guests cannot upload a logo', function () {
    $this->postJson(route('admin.technology-icons.upload'), [
        'file' => UploadedFile::fake()->createWithContent('logo.svg', svg()),
        'slug' => 'logo',
    ])->assertUnauthorized();
});

test('an uploaded svg is saved in the library', function () {
    // Prologue XML et commentaire : typiques d'un export Inkscape ou Illustrator.
    $content = '<?xml version="1.0" encoding="UTF-8"?><!-- Generator: Inkscape -->'.svg('<path fill="#f00"/>');

    $response = $this->actingAs(User::factory()->create())->postJson(route('admin.technology-icons.upload'), [
        'file' => UploadedFile::fake()->createWithContent('mon-logo.svg', $content),
        'slug' => 'mon-logo',
    ]);

    $response->assertCreated()
        ->assertJsonPath('icon.slug', 'mon-logo')
        ->assertJsonPath('icon.light_url', fn ($url) => str_contains($url, '/icons/tech/mon-logo.svg'));
    expect(File::get("{$this->iconDirectory}/mon-logo.svg"))->toBe($content);
});

test('an uploaded svg can be saved for one theme only', function () {
    $this->actingAs(User::factory()->create())->postJson(route('admin.technology-icons.upload'), [
        'file' => UploadedFile::fake()->createWithContent('logo.svg', svg('<path fill="#fff"/>')),
        'slug' => 'logo',
        'theme' => 'dark',
    ])->assertCreated();

    expect(File::exists("{$this->iconDirectory}/logo-dark.svg"))->toBeTrue()
        ->and(File::exists("{$this->iconDirectory}/logo.svg"))->toBeFalse();
});

test('uploading refuses a logo name already in use', function () {
    File::ensureDirectoryExists($this->iconDirectory);
    File::put("{$this->iconDirectory}/logo.svg", svg());

    $this->actingAs(User::factory()->create())->postJson(route('admin.technology-icons.upload'), [
        'file' => UploadedFile::fake()->createWithContent('logo.svg', svg()),
        'slug' => 'logo',
    ])->assertJsonValidationErrors(['slug' => 'Ce nom de logo est déjà utilisé.']);
});

test('uploading refuses a file that is not an svg', function () {
    $this->actingAs(User::factory()->create())->postJson(route('admin.technology-icons.upload'), [
        'file' => UploadedFile::fake()->image('logo.png'),
        'slug' => 'logo',
    ])->assertJsonValidationErrors('file');
});

test('uploading refuses an oversized svg', function () {
    $this->actingAs(User::factory()->create())->postJson(route('admin.technology-icons.upload'), [
        'file' => UploadedFile::fake()->create('logo.svg', 300),
        'slug' => 'logo',
    ])->assertJsonValidationErrors('file');
});

test('uploading refuses an svg with script or entity declarations', function (string $body) {
    $this->actingAs(User::factory()->create())->postJson(route('admin.technology-icons.upload'), [
        'file' => UploadedFile::fake()->createWithContent('logo.svg', $body),
        'slug' => 'logo',
    ])->assertJsonValidationErrors(['file' => 'Ce fichier n\'est pas un SVG valide, ou il contient du script.']);
    expect(File::exists("{$this->iconDirectory}/logo.svg"))->toBeFalse();
})->with([
    'script' => svg('<script>alert(1)</script>'),
    'event handler' => svg('<path onload="alert(1)"/>'),
    'entity' => '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x "y">]>'.svg(),
    'not an svg' => '<html></html>',
]);

test('imported logos become available in the library', function () {
    Http::fake(['api.iconify.design/logos/docker.svg' => Http::response(svg('<path fill="#00f"/>'))]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'logos:docker', 'slug' => 'docker'])
        ->assertCreated();

    expect(collect(app(TechnologyIconLibrary::class)->all())->pluck('slug')->all())->toBe(['docker']);
});

test('importing refuses a logo name already in use', function () {
    File::ensureDirectoryExists($this->iconDirectory);
    File::put("{$this->iconDirectory}/laravel-dark.svg", svg());

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => 'laravel'])
        ->assertJsonValidationErrors(['slug' => 'Ce nom de logo est déjà utilisé.']);
    Http::assertNothingSent();
});

test('importing refuses an invalid logo name', function (string $slug) {
    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => $slug])
        ->assertJsonValidationErrors('slug');
    Http::assertNothingSent();
})->with(['theme suffix' => 'laravel-light', 'uppercase' => 'Laravel', 'path traversal' => '../laravel', 'spaces' => 'my logo']);

test('importing refuses a catalog collection that is not offered', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'mdi:home', 'slug' => 'home'])
        ->assertJsonValidationErrors('icon');
    Http::assertNothingSent();
});

test('importing refuses a suspicious or missing svg', function (string $body, int $status) {
    Http::fake(['api.iconify.design/logos/laravel.svg' => Http::response($body, $status)]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => 'laravel'])
        ->assertStatus(502);
    expect(File::exists("{$this->iconDirectory}/laravel.svg"))->toBeFalse();
})->with([
    'script' => [svg('<script>alert(1)</script>'), 200],
    'event handler' => [svg('<path onload="alert(1)"/>'), 200],
    'not an svg' => ['<html></html>', 200],
    'not found' => ['404', 404],
]);
