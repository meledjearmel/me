<?php

use App\Enums\TestimonialStatus;
use App\Models\Profile;
use App\Models\Testimonial;

beforeEach(function () {
    Profile::factory()->create();
});

test('a submitted review stays pending and is stored in the visitor language', function () {
    $this->post('/en/testimonials', [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'author_role' => 'CTO, Acme',
        'content' => 'Very reliable work and clear communication throughout the project.',
    ])->assertRedirect();

    $testimonial = Testimonial::query()->firstOrFail();

    expect($testimonial->status)->toBe(TestimonialStatus::Pending)
        ->and($testimonial->getTranslation('content', 'en'))->toContain('Very reliable')
        ->and($testimonial->submitted_at)->not->toBeNull();
});

test('a pending review is not shown on the home page', function () {
    $this->post('/fr/testimonials', [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'content' => 'Un travail sérieux et une communication claire du début à la fin.',
    ]);

    $this->get('/fr')->assertInertia(fn ($page) => $page->has('testimonials', 0));
});

test('a review needs a name, an email and a real message', function () {
    $this->post('/fr/testimonials', [
        'author_name' => '',
        'author_email' => 'not-an-email',
        'content' => 'Trop court',
    ])->assertSessionHasErrors(['author_name', 'author_email', 'content']);

    expect(Testimonial::query()->count())->toBe(0);
});

test('the honeypot field rejects bots on reviews', function () {
    $this->post('/fr/testimonials', [
        'author_name' => 'Bot',
        'author_email' => 'bot@example.test',
        'content' => 'Un message suffisamment long pour passer la validation.',
        'website' => 'https://spam.test',
    ])->assertSessionHasErrors('website');
});
