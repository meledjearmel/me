<?php

use App\Enums\TestimonialStatus;
use App\Jobs\ProcessTestimonialVideo;
use App\Models\Experience;
use App\Models\Project;
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

test('an SRT captions file is converted to WebVTT and served as a track', function () {
    Storage::fake('public');
    Queue::fake();
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create(['status' => TestimonialStatus::Approved]);
    $testimonial->attachVideo(UploadedFile::fake()->create('avis.mp4', 100, 'video/mp4'));
    $srt = "1\r\n00:00:01,000 --> 00:00:03,500\r\nUn travail remarquable.\r\n";

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => 'approved',
        'video_captions_files' => ['fr' => UploadedFile::fake()->createWithContent('avis.srt', $srt)],
    ])->assertSessionHasNoErrors();

    $tracks = $testimonial->fresh()->videoData()['captions'];
    expect($tracks)->toHaveCount(1)->and($tracks[0]['language'])->toBe('fr');

    $this->get($tracks[0]['url'])
        ->assertOk()
        ->assertHeader('Content-Type', 'text/vtt; charset=UTF-8')
        ->assertSee("WEBVTT\n\n1\n00:00:01.000 --> 00:00:03.500", false);
});

test('a word-timed JSON transcript is split into WebVTT cues', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create(['status' => TestimonialStatus::Approved]);
    $word = fn (string $text, float $start, float $end): array => ['text' => $text, 'start_time' => $start, 'end_time' => $end];
    $json = json_encode(['segments' => [['words' => [
        $word('Petite', 1.9, 2.18), $word(' ', 2.18, 2.2), $word('vidéo', 2.2, 2.48), $word(' ', 2.48, 2.52),
        $word('pour', 2.52, 2.62), $word(' ', 2.62, 2.64), $word('te', 2.66, 2.76), $word(' ', 2.76, 2.8),
        $word('remercier.', 2.8, 3.12), $word(' ', 3.12, 3.22), $word('Voilà.', 63.0, 63.26),
    ]]]]);

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => 'approved',
        'video_captions_files' => ['fr' => UploadedFile::fake()->createWithContent('avis.mp4.json', $json)],
    ])->assertSessionHasNoErrors();

    expect($testimonial->fresh()->getTranslation('video_captions', 'fr'))->toBe(
        "WEBVTT\n\n00:00:01.900 --> 00:00:03.120\nPetite vidéo pour te remercier.\n\n00:01:03.000 --> 00:01:03.260\nVoilà.\n"
    );
});

test('a captions file without timed cues is refused', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create();

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => 'approved',
        'video_captions_files' => ['en' => UploadedFile::fake()->createWithContent('avis.vtt', 'Juste du texte.')],
    ])->assertSessionHasErrors('video_captions_files.en');
});

test('a captions track can be removed, and captions of unapproved reviews are not served', function () {
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create(['status' => TestimonialStatus::Pending]);
    $testimonial->setTranslation('video_captions', 'fr', "WEBVTT\n\n00:00.000 --> 00:01.000\nBonjour\n")->save();

    $this->get(route('testimonials.captions', [$testimonial, 'fr']))->assertNotFound();

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => 'pending',
        'remove_video_captions' => ['fr'],
    ])->assertSessionHasNoErrors();

    expect($testimonial->fresh()->captionTracks())->toBe([]);
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

test('a video heavier than post_max_size returns to the form with an error', function () {
    $user = User::factory()->create();
    $testimonial = Testimonial::factory()->create();
    $edit = route('admin.testimonials.edit', $testimonial);

    $this->actingAs($user)
        ->from($edit)
        ->call('PUT', route('admin.testimonials.update', $testimonial), server: ['CONTENT_LENGTH' => PHP_INT_MAX])
        ->assertRedirect($edit)
        ->assertSessionHasErrors('video');
});

test('a testimonial can be linked to several projects and experiences, and unlinked', function () {
    $user = User::factory()->create();
    $projects = Project::factory()->count(2)->create();
    $experience = Experience::factory()->create();
    $testimonial = Testimonial::factory()->create();

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => TestimonialStatus::Approved->value,
        'project_ids' => $projects->modelKeys(),
        'experience_ids' => [$experience->id],
    ])->assertSessionHasNoErrors();

    expect($testimonial->projects()->pluck('projects.id')->all())->toEqualCanonicalizing($projects->modelKeys())
        ->and($testimonial->experiences()->pluck('experiences.id')->all())->toBe([$experience->id]);

    $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
        'status' => TestimonialStatus::Approved->value,
        'project_ids' => [''],
    ])->assertSessionHasNoErrors();

    expect($testimonial->projects()->count())->toBe(0)
        ->and($testimonial->experiences()->count())->toBe(1);
});
