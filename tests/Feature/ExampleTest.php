<?php

use App\Models\Profile;

test('returns a successful response', function () {
    Profile::factory()->create();

    $response = $this->get(route('home', ['locale' => 'fr']));

    $response->assertOk();
});
