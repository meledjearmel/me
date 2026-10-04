<?php

use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('the contact button opens the drawer by default', function () {
    Profile::factory()->create();

    $this->get('/fr')->assertInertia(fn ($page) => $page->where('contactOpensDrawer', true));
});

test('the contact button behaviour can be changed from the admin', function () {
    Profile::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.site-settings.edit'))
        ->assertOk();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.site-settings.update'), ['contact_opens_drawer' => '0'])
        ->assertSessionHasNoErrors();

    expect(SiteSetting::current()->contact_opens_drawer)->toBeFalse();

    $this->get('/fr')->assertInertia(fn ($page) => $page->where('contactOpensDrawer', false));
});

test('the site settings can be read and changed through the API', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson(route('api.v1.site-settings.update'), ['contact_opens_drawer' => false])
        ->assertOk()
        ->assertJsonPath('contact_opens_drawer', false);

    $this->getJson(route('api.v1.site-settings.show'))->assertJsonPath('contact_opens_drawer', false);
});

test('guests cannot change the site settings', function () {
    $this->get(route('admin.site-settings.edit'))->assertRedirect(route('login'));
});

test('the blog is shown by default and can be hidden from the admin', function () {
    Profile::factory()->create();

    $this->get('/fr')->assertInertia(fn ($page) => $page->where('blogEnabled', true));

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.site-settings.update'), ['blog_enabled' => '0'])
        ->assertSessionHasNoErrors();

    expect(SiteSetting::current()->blog_enabled)->toBeFalse();

    $this->get('/fr')->assertInertia(fn ($page) => $page->where('blogEnabled', false));
});

test('the site shows me as available by default', function () {
    Profile::factory()->create();

    $this->get('/fr')->assertInertia(fn ($page) => $page->where('availability', ['status' => 'available', 'from' => null]));
});

test('an availability date can be set and the site shows it until it passes', function () {
    Profile::factory()->create();
    $date = now()->addMonth()->toDateString();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.site-settings.update'), ['availability_status' => 'from', 'available_from' => $date])
        ->assertSessionHasNoErrors();

    $this->get('/fr')->assertInertia(fn ($page) => $page->where('availability', ['status' => 'from', 'from' => $date]));

    $this->travel(2)->months();

    $this->get('/fr')->assertInertia(fn ($page) => $page->where('availability', ['status' => 'available', 'from' => null]));
});

test('the "available from" status needs a future date', function (array $payload) {
    $this->actingAs(User::factory()->create())
        ->patch(route('admin.site-settings.update'), ['availability_status' => 'from', ...$payload])
        ->assertSessionHasErrors('available_from');
})->with([
    'missing' => [[]],
    'past' => [['available_from' => '2020-01-01']],
]);

test('a past date does not block saving another availability status', function () {
    Profile::factory()->create();
    SiteSetting::current()->update(['available_from' => '2020-01-01']);

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.site-settings.update'), ['availability_status' => 'unavailable', 'available_from' => '2020-01-01'])
        ->assertSessionHasNoErrors();

    $this->get('/fr')->assertInertia(fn ($page) => $page->where('availability.status', 'unavailable'));
});

test('the availability can be read and changed through the API', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson(route('api.v1.site-settings.update'), ['availability_status' => 'unavailable'])
        ->assertOk()
        ->assertJsonPath('availability_status', 'unavailable')
        ->assertJsonPath('available_from', null);
});
