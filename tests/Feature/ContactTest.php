<?php

use App\Jobs\SendPushNotification;
use App\Mail\ContactReceivedMail;
use App\Mail\VisitorAcknowledgementMail;
use App\Models\Contact;
use App\Models\Profile;
use Illuminate\Support\Facades\Mail;
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

test('a contact message notifies the owner by email, replying to the visitor', function () {
    Queue::fake();
    Mail::fake();
    Profile::factory()->create(['email' => 'owner@example.test']);

    $this->post('/fr/contact', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Bonjour',
        'message' => 'Je souhaite discuter de mon projet.',
    ])->assertRedirect();

    Mail::assertQueued(ContactReceivedMail::class, fn (ContactReceivedMail $mail) => $mail->hasTo('owner@example.test')
        && $mail->hasReplyTo('jane@example.com')
        && $mail->contact->subject === 'Bonjour');
});

test('the visitor receives an acknowledgement in the page language, without their own text', function () {
    Queue::fake();
    Mail::fake();
    Profile::factory()->create(['email' => 'owner@example.test']);

    $this->post('/en/contact', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Visit cheap-pills.example',
        'message' => 'Spam text that must not be echoed back.',
    ])->assertRedirect();

    Mail::assertQueued(VisitorAcknowledgementMail::class, function (VisitorAcknowledgementMail $mail) {
        return $mail->hasTo('jane@example.com')
            && $mail->kind === 'contact'
            && $mail->locale === 'en'
            && ! str_contains($mail->render(), 'cheap-pills')
            && ! str_contains($mail->render(), 'Spam text');
    });
});

test('the contact form is rate limited', function () {
    Queue::fake();
    Mail::fake();
    Profile::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post('/fr/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Bonjour',
            'message' => 'Un message.',
        ])->assertRedirect();
    }

    $this->post('/fr/contact', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Bonjour',
        'message' => 'Un message.',
    ])->assertTooManyRequests();
});
