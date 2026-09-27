<?php

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.testimonials.index'))->assertRedirect(route('login'));
});

test('authenticated users can approve a testimonial', function () {
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create(['status' => TestimonialStatus::Pending]);

    $response = $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => TestimonialStatus::Approved->value,
    ]);

    $response->assertRedirect(route('admin.testimonials.index'));
    expect($testimonial->fresh()->status)->toBe(TestimonialStatus::Approved);
});

test('authenticated users can delete a testimonial', function () {
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create();

    $this->actingAs($user)->delete(route('admin.testimonials.destroy', $testimonial))
        ->assertRedirect(route('admin.testimonials.index'));

    $this->assertSoftDeleted($testimonial);
});

test('updating a testimonial requires a valid status', function () {
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => 'not-a-status',
    ]);

    $response->assertSessionHasErrors(['status']);
});

test('a fourth featured review is refused', function () {
    $user = User::factory()->create();
    Testimonial::factory()->count(3)->create(['is_featured' => true]);
    $fourth = Testimonial::factory()->create(['is_featured' => false]);

    $this->actingAs($user)->put(route('admin.testimonials.update', $fourth), [
        'status' => 'approved',
        'is_featured' => 1,
    ])->assertSessionHasErrors('is_featured');

    expect($fourth->fresh()->is_featured)->toBeFalse();
});

test('an already featured review can be saved again when three are featured', function () {
    $user = User::factory()->create();
    $featured = Testimonial::factory()->count(3)->create(['is_featured' => true])->first();

    $this->actingAs($user)->put(route('admin.testimonials.update', $featured), [
        'status' => 'approved',
        'is_featured' => 1,
    ])->assertSessionHasNoErrors();
});

test('authenticated users can correct a testimonial\'s wording', function () {
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create(['content' => ['fr' => 'Sa a été un plaisir']]);

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => $testimonial->status->value,
        'author_name' => 'Nouveau nom',
        'author_role' => 'CTO',
        'content' => ['fr' => 'Ça a été un plaisir.', 'en' => 'It was a pleasure.'],
    ])->assertRedirect(route('admin.testimonials.index'));

    $testimonial->refresh();
    expect($testimonial->author_name)->toBe('Nouveau nom')
        ->and($testimonial->author_role)->toBe('CTO')
        ->and($testimonial->getTranslation('content', 'fr'))->toBe('Ça a été un plaisir.')
        ->and($testimonial->getTranslation('content', 'en'))->toBe('It was a pleasure.');
});

test('correcting content requires both languages together', function () {
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create();

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => $testimonial->status->value,
        'content' => ['fr' => 'Texte corrigé.'],
    ])->assertSessionHasErrors(['content.en']);
});
