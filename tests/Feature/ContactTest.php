<?php

use App\Jobs\SendPushNotification;
use App\Models\Contact;
use Illuminate\Support\Facades\Queue;

test('a visitor can submit the contact form', function () {
    Queue::fake();

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
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->data['type'] === 'contact');
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
