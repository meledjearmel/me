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
