<?php

use App\Enums\TechnologyCategory;
use App\Models\Technology;
use App\Models\User;
use App\Services\TechnologyIconLibrary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->iconDirectory = sys_get_temp_dir().'/technology-icons-'.Str::random(12);

    // Bibliothèque isolée : les imports n'écrivent pas dans public/icons/tech.
    $this->app->instance(TechnologyIconLibrary::class, new TechnologyIconLibrary($this->iconDirectory));

    Http::preventStrayRequests();
});

afterEach(function () {
    File::deleteDirectory($this->iconDirectory);
});

function iconSvg(string $body = '<path d="M0 0h1v1z" fill="currentColor"/>'): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.$body.'</svg>';
}

test('les logos exigent un jeton', function () {
    $this->getJson(route('api.v1.technology-icons.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.technology-icons.search', ['q' => 'laravel']))->assertUnauthorized();
    $this->postJson(route('api.v1.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => 'laravel'])->assertUnauthorized();
    $this->postJson(route('api.v1.technology-icons.upload'), ['slug' => 'logo'])->assertUnauthorized();
});

test('la bibliothèque liste les logos avec leurs variantes de thème', function () {
    Sanctum::actingAs(User::factory()->create());
    File::ensureDirectoryExists($this->iconDirectory);
    File::put("{$this->iconDirectory}/github-light.svg", iconSvg());
    File::put("{$this->iconDirectory}/github-dark.svg", iconSvg());
    File::put("{$this->iconDirectory}/react.svg", iconSvg('<path fill="#0ff"/>'));

    $response = $this->getJson(route('api.v1.technology-icons.index'))->assertOk();

    expect($response->json('data.*.slug'))->toBe(['github', 'react'])
        ->and($response->json('data.0.light_url'))->toContain('/icons/tech/github-light.svg')
        ->and($response->json('data.0.dark_url'))->toContain('/icons/tech/github-dark.svg')
        ->and($response->json('data.1.light_url'))->toBe($response->json('data.1.dark_url'));
});

test('la recherche liste les logos du catalogue', function () {
    Sanctum::actingAs(User::factory()->create());
    Http::fake(['api.iconify.design/search*' => Http::response(['icons' => ['logos:laravel', 'mdi:laravel']])]);

    $this->getJson(route('api.v1.technology-icons.search', ['q' => 'laravel']))
        ->assertOk()
        ->assertExactJson(['data' => [[
            'id' => 'logos:laravel',
            'name' => 'laravel',
            'collection' => 'logos',
            'preview_url' => 'https://api.iconify.design/logos/laravel.svg',
        ]]]);
});

test('la recherche, l\'import et l\'envoi de logos sont limités à 30 requêtes par minute', function () {
    Sanctum::actingAs(User::factory()->create());
    Http::fake(['api.iconify.design/search*' => Http::response(['icons' => []])]);

    foreach (range(1, 30) as $attempt) {
        $this->getJson(route('api.v1.technology-icons.search', ['q' => 'laravel']))->assertOk();
    }

    $this->getJson(route('api.v1.technology-icons.search', ['q' => 'laravel']))->assertTooManyRequests();
    $this->postJson(route('api.v1.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => 'laravel'])->assertTooManyRequests();
    $this->postJson(route('api.v1.technology-icons.upload'), ['slug' => 'logo'])->assertTooManyRequests();
    $this->getJson(route('api.v1.technology-icons.index'))->assertOk();
});

test('la recherche exige deux caractères et signale un catalogue indisponible', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.technology-icons.search', ['q' => 'a']))->assertJsonValidationErrors('q');

    Http::fake(['api.iconify.design/search*' => Http::response('', 500)]);

    $this->getJson(route('api.v1.technology-icons.search', ['q' => 'laravel']))
        ->assertStatus(502)
        ->assertJsonPath('message', 'Le catalogue de logos est momentanément indisponible.');
});

test('un logo du catalogue s\'importe', function () {
    Sanctum::actingAs(User::factory()->create());
    Http::fake(['api.iconify.design/simple-icons/github.svg' => Http::response(iconSvg())]);

    $this->postJson(route('api.v1.technology-icons.store'), ['icon' => 'simple-icons:github', 'slug' => 'github'])
        ->assertCreated()
        ->assertJsonPath('slug', 'github')
        ->assertJsonPath('light_url', fn ($url) => str_contains($url, '/icons/tech/github-light.svg'))
        ->assertJsonPath('dark_url', fn ($url) => str_contains($url, '/icons/tech/github-dark.svg'));
    expect(File::exists("{$this->iconDirectory}/github-light.svg"))->toBeTrue()
        ->and(File::exists("{$this->iconDirectory}/github-dark.svg"))->toBeTrue();
});

test('l\'import d\'un logo pour un seul thème ajoute la variante d\'un logo existant', function () {
    Sanctum::actingAs(User::factory()->create());
    File::ensureDirectoryExists($this->iconDirectory);
    File::put("{$this->iconDirectory}/github.svg", iconSvg('<path fill="#f00"/>'));
    Http::fake(['api.iconify.design/simple-icons/github.svg' => Http::response(iconSvg())]);

    $this->postJson(route('api.v1.technology-icons.store'), ['icon' => 'simple-icons:github', 'slug' => 'github', 'theme' => 'dark'])
        ->assertCreated()
        ->assertJsonPath('dark_url', fn ($url) => str_contains($url, '/icons/tech/github-dark.svg'));
    expect(File::exists("{$this->iconDirectory}/github-light.svg"))->toBeFalse()
        ->and(File::get("{$this->iconDirectory}/github.svg"))->toContain('#f00');
});

test('l\'import refuse un nom déjà pris, une collection inconnue et un SVG suspect', function () {
    Sanctum::actingAs(User::factory()->create());
    File::ensureDirectoryExists($this->iconDirectory);
    File::put("{$this->iconDirectory}/laravel.svg", iconSvg());

    $this->postJson(route('api.v1.technology-icons.store'), ['icon' => 'logos:laravel', 'slug' => 'laravel'])
        ->assertJsonValidationErrors(['slug' => 'Ce nom de logo est déjà utilisé.']);
    $this->postJson(route('api.v1.technology-icons.store'), ['icon' => 'mdi:home', 'slug' => 'home'])
        ->assertJsonValidationErrors('icon');
    Http::assertNothingSent();

    Http::fake(['api.iconify.design/logos/docker.svg' => Http::response(iconSvg('<script>alert(1)</script>'))]);

    $this->postJson(route('api.v1.technology-icons.store'), ['icon' => 'logos:docker', 'slug' => 'docker'])->assertStatus(502);
    expect(File::exists("{$this->iconDirectory}/docker.svg"))->toBeFalse();
});

test('un SVG envoyé s\'enregistre, éventuellement pour un seul thème', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson(route('api.v1.technology-icons.upload'), [
        'file' => UploadedFile::fake()->createWithContent('logo.svg', iconSvg('<path fill="#f00"/>')),
        'slug' => 'mon-logo',
    ])
        ->assertCreated()
        ->assertJsonPath('slug', 'mon-logo')
        ->assertJsonPath('light_url', fn ($url) => str_contains($url, '/icons/tech/mon-logo.svg'));

    $this->postJson(route('api.v1.technology-icons.upload'), [
        'file' => UploadedFile::fake()->createWithContent('logo.svg', iconSvg('<path fill="#fff"/>')),
        'slug' => 'mon-logo',
        'theme' => 'dark',
    ])->assertCreated()->assertJsonPath('dark_url', fn ($url) => str_contains($url, '/icons/tech/mon-logo-dark.svg'));
});

test('l\'envoi refuse un fichier qui n\'est pas un SVG sûr', function (callable $file) {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson(route('api.v1.technology-icons.upload'), ['file' => $file(), 'slug' => 'logo'])
        ->assertJsonValidationErrors('file');
    expect(File::exists("{$this->iconDirectory}/logo.svg"))->toBeFalse();
})->with([
    'png' => [fn () => UploadedFile::fake()->image('logo.png')],
    'trop gros' => [fn () => UploadedFile::fake()->create('logo.svg', 300)],
    'script' => [fn () => UploadedFile::fake()->createWithContent('logo.svg', iconSvg('<script>alert(1)</script>'))],
]);

test('une technologie expose les URL de son logo et n\'accepte qu\'un logo existant', function () {
    Sanctum::actingAs(User::factory()->create());
    File::ensureDirectoryExists($this->iconDirectory);
    File::put("{$this->iconDirectory}/github-light.svg", iconSvg());
    File::put("{$this->iconDirectory}/github-dark.svg", iconSvg());
    $payload = ['name' => 'GitHub', 'category' => TechnologyCategory::Qualite->value];

    $this->postJson(route('api.v1.technologies.store'), [...$payload, 'icon' => 'github'])
        ->assertCreated()
        ->assertJsonPath('icon', 'github')
        ->assertJsonPath('icon_light_url', fn ($url) => str_contains($url, '/icons/tech/github-light.svg'))
        ->assertJsonPath('icon_dark_url', fn ($url) => str_contains($url, '/icons/tech/github-dark.svg'));

    $this->postJson(route('api.v1.technologies.store'), [...$payload, 'name' => 'Autre', 'icon' => 'aucune-icone'])
        ->assertJsonValidationErrors(['icon' => 'Ce logo n\'existe pas dans la bibliothèque.']);
    expect(Technology::query()->where('name', 'Autre')->exists())->toBeFalse();
});

test('une technologie garde un logo déjà enregistré sans fichier', function () {
    Sanctum::actingAs(User::factory()->create());
    $technology = Technology::factory()->create(['icon' => 'shield']);

    $this->putJson(route('api.v1.technologies.update', $technology), [
        'name' => 'Renommée',
        'category' => $technology->category->value,
        'icon' => 'shield',
    ])->assertOk()->assertJsonPath('icon', 'shield')->assertJsonPath('icon_light_url', null);
});
