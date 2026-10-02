<?php

use App\Enums\TestimonialStatus;
use App\Jobs\ProcessTestimonialVideo;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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

test('a video and a highlight can be added to a testimonial', function () {
    Storage::fake('public');
    Queue::fake();
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create();

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => 'approved',
        'highlight' => ['fr' => 'Un travail remarquable.', 'en' => 'Remarkable work.'],
        'video' => UploadedFile::fake()->create('avis.mp4', 2048, 'video/mp4'),
    ])->assertRedirect(route('admin.testimonials.index'));

    $testimonial->refresh();
    expect($testimonial->getTranslation('highlight', 'fr'))->toBe('Un travail remarquable.')
        ->and($testimonial->getFirstMedia(Testimonial::VIDEO_COLLECTION))->not->toBeNull();

    Queue::assertPushed(ProcessTestimonialVideo::class);
});

test('a testimonial video must be a video file', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create();

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => 'approved',
        'video' => UploadedFile::fake()->create('avis.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('video');
});

test('the video of a testimonial can be removed', function () {
    Storage::fake('public');
    Queue::fake();
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create();
    $testimonial->attachVideo(UploadedFile::fake()->create('avis.mp4', 100, 'video/mp4'));

    $this->actingAs($user)->delete(route('admin.testimonials.video.destroy', $testimonial))
        ->assertRedirect(route('admin.testimonials.edit', $testimonial));

    expect($testimonial->fresh()->videoData())->toBeNull();
});

test('processing a video without ffmpeg keeps the original file', function () {
    Storage::fake('public');
    Queue::fake();
    config(['media-library.ffmpeg_path' => 'ffmpeg-introuvable']);
    $testimonial = Testimonial::factory()->create();
    $testimonial->attachVideo(UploadedFile::fake()->create('avis.mp4', 100, 'video/mp4'));

    (new ProcessTestimonialVideo($testimonial->getFirstMedia(Testimonial::VIDEO_COLLECTION)))->handle();

    expect($testimonial->fresh()->videoData())
        ->not->toBeNull()
        ->poster_url->toBeNull()
        ->duration->toBeNull();
});
