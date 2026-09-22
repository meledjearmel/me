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
