<?php

use App\Models\Profile;

beforeEach(function () {
    Profile::factory()->create();
});

test('the home redirect follows a French browser', function () {
    $this->get('/', ['Accept-Language' => 'fr-CI,fr;q=0.9,en;q=0.8'])->assertRedirect('/fr');
});

test('the home redirect sends any other browser language to English', function () {
    $this->get('/', ['Accept-Language' => 'es-ES,es;q=0.9'])->assertRedirect('/en');
    $this->get('/', ['Accept-Language' => 'en-US'])->assertRedirect('/en');
});

test('a link without language is redirected to the same page in the visitor language', function () {
    $this->get('/about', ['Accept-Language' => 'fr-FR'])->assertRedirect('/fr/about');
    $this->get('/projects/app-station?ref=cv', ['Accept-Language' => 'de-DE'])
        ->assertRedirect('/en/projects/app-station?ref=cv');
});

test('an address that matches no page shows the public error page in the visitor language', function () {
    $this->get('/does-not-exist', ['Accept-Language' => 'en-GB'])
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('public/error')
            ->where('status', 404)
            ->where('locale', 'en')
        );
});

test('an unknown page under a language shows the error page in that language', function () {
    $this->get('/fr/nope', ['Accept-Language' => 'en-GB'])
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('public/error')
            ->where('locale', 'fr')
        );
});

test('an unknown address answers 404 whatever the method', function () {
    $this->post('/does-not-exist')->assertNotFound();
    $this->deleteJson('/api/v1/nope')->assertNotFound();
});
