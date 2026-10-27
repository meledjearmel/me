<?php

use App\Models\User;

test('the administration lives under /console', function () {
    expect(route('dashboard', absolute: false))->toBe('/console')
        ->and(route('admin.posts.index', absolute: false))->toBe('/console/posts')
        ->and(route('profile.edit', absolute: false))->toBe('/console/settings/profile');
});

test('the old administration addresses redirect to the console', function (string $old, string $new) {
    $this->get($old)->assertRedirect($new)->assertStatus(301);
})->with([
    'dashboard' => ['/dashboard', '/console'],
    'admin page' => ['/admin/posts?status=draft', '/console/posts'],
    'settings page' => ['/settings/security', '/console/settings/security'],
]);

test('the console renders the palette chosen in the cookie', function () {
    $this->actingAs(User::factory()->create())
        ->withUnencryptedCookie('palette', 'lime-charcoal')
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-palette="lime-charcoal"', false);
});

test('an unknown palette falls back to the default one', function () {
    $this->actingAs(User::factory()->create())
        ->withUnencryptedCookie('palette', '"><script>')
        ->get(route('dashboard'))
        ->assertSee('data-palette="gold-graphite"', false);
});
