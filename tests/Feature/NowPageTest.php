<?php

use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Profile::factory()->create();
});

test('the now page does not exist without a French text', function () {
    $this->get('/fr/now')->assertNotFound();
    $this->get('/fr')->assertInertia(fn ($page) => $page->where('nowEnabled', false));
    $this->get('/sitemap.xml')->assertDontSee('/fr/now');
});

test('the now page shows the text from the admin, the English page falling back to French', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('admin.now-page.update'), ['now_content' => ['fr' => "Je construis mon portfolio.\n\n- Laravel\n- React", 'en' => '']])
        ->assertSessionHasNoErrors();

    $this->get('/fr/now')->assertOk()->assertInertia(fn ($page) => $page
        ->component('public/now')
        ->where('text', "Je construis mon portfolio.\n\n- Laravel\n- React")
        ->where('contentLocale', 'fr')
        ->where('nowEnabled', true));

    $this->get('/en/now')->assertInertia(fn ($page) => $page->where('contentLocale', 'fr'));
    $this->get('/sitemap.xml')->assertSee('/fr/now');
});

test('the update date only moves when the text changes', function () {
    $user = User::factory()->create();
    $this->travelTo(now()->subDays(10));
    $this->actingAs($user)->put(route('admin.now-page.update'), ['now_content' => ['fr' => 'Texte']]);
    $firstUpdate = SiteSetting::current()->now_updated_at;
    $this->travelBack();

    $this->actingAs($user)->put(route('admin.now-page.update'), ['now_content' => ['fr' => 'Texte']]);
    expect(SiteSetting::current()->now_updated_at->equalTo($firstUpdate))->toBeTrue();

    $this->actingAs($user)->put(route('admin.now-page.update'), ['now_content' => ['fr' => 'Nouveau texte']]);
    expect(SiteSetting::current()->now_updated_at->isAfter($firstUpdate))->toBeTrue();
});

test('the now page can be read and written through the site settings API', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson(route('api.v1.site-settings.update'), ['now_content' => ['fr' => 'Depuis l’app', 'en' => 'From the app']])
        ->assertOk()
        ->assertJsonPath('now_content.en', 'From the app')
        ->assertJsonPath('now_updated_at', fn ($date) => $date !== null);
});
