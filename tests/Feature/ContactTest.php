<?php

use App\Models\Contact;

test('a visitor can submit the contact form', function () {
    $response = $this->post('/fr/contact', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Bonjour',
        'message' => 'Je souhaite discuter d\'un projet.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('contacts', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'status' => 'new',
    ]);
});

test('the contact form requires the mandatory fields', function () {
    $response = $this->post('/fr/contact', []);

    $response->assertSessionHasErrors(['name', 'email', 'message']);
});

test('filling the honeypot field silently rejects the submission', function () {
    $response = $this->post('/fr/contact', [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'message' => 'Spam',
        'website' => 'https://spam.example.com',
    ]);

    $response->assertSessionHasErrors(['website']);
    expect(Contact::query()->where('email', 'bot@example.com')->exists())->toBeFalse();
});
