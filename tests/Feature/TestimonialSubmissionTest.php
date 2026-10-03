<?php

use App\Enums\ProjectStatus;
use App\Enums\TestimonialStatus;
use App\Jobs\ProcessTestimonialVideo;
use App\Jobs\SendPushNotification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Profile::factory()->create();
});

test('a submitted review stays pending and is stored in the visitor language', function () {
    Queue::fake();

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
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->data['type'] === 'testimonial');
});

test('a review left from a project, an experience or an education is linked to it', function () {
    Queue::fake();
    $project = Project::factory()->create();
    $experience = Experience::factory()->create();
    $education = Education::factory()->create();

    $this->post('/fr/testimonials', [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'content' => 'Un travail sérieux et une communication claire du début à la fin.',
        'project_id' => $project->id,
        'experience_id' => $experience->id,
        'education_id' => $education->id,
    ])->assertSessionHasNoErrors();

    $testimonial = Testimonial::query()->firstOrFail();

    expect($testimonial->project_id)->toBe($project->id)
        ->and($testimonial->experience_id)->toBe($experience->id)
        ->and($testimonial->education_id)->toBe($education->id);
});

test('a review cannot be linked to an archived project', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Archived]);

    $this->post('/fr/testimonials', [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'content' => 'Un travail sérieux et une communication claire du début à la fin.',
        'project_id' => $project->id,
    ])->assertSessionHasErrors('project_id');

    expect(Testimonial::query()->count())->toBe(0);
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

test('a visitor can attach a video to a review, which stays pending', function () {
    Storage::fake('public');
    Queue::fake();

    $this->post('/fr/testimonials', [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'content' => 'Un travail sérieux et une communication claire du début à la fin.',
        'video' => UploadedFile::fake()->create('avis.mp4', 4096, 'video/mp4'),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $testimonial = Testimonial::query()->firstOrFail();

    expect($testimonial->status)->toBe(TestimonialStatus::Pending)
        ->and($testimonial->videoData())->not->toBeNull();
    Queue::assertPushed(ProcessTestimonialVideo::class);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->title === 'Nouvel avis vidéo');
});

test('a review video must be a video under 95 MB', function (UploadedFile $file) {
    Storage::fake('public');

    $this->post('/fr/testimonials', [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'content' => 'Un travail sérieux et une communication claire du début à la fin.',
        'video' => $file,
    ])->assertSessionHasErrors('video');

    expect(Testimonial::query()->count())->toBe(0);
})->with([
    'not a video' => fn () => UploadedFile::fake()->create('avis.pdf', 100, 'application/pdf'),
    'too heavy' => fn () => UploadedFile::fake()->create('avis.mp4', 97281, 'video/mp4'),
]);

test('a review video is refused when video reviews are disabled in the admin', function () {
    Storage::fake('public');
    Profile::query()->update(['testimonial_video_enabled' => false]);

    $this->post('/fr/testimonials', [
        'author_name' => 'Sam Client',
        'author_email' => 'sam@example.test',
        'content' => 'Un travail sérieux et une communication claire du début à la fin.',
        'video' => UploadedFile::fake()->create('avis.mp4', 100, 'video/mp4'),
    ])->assertSessionHasErrors('video');

    $this->get('/fr/testimonials')->assertInertia(fn ($page) => $page->where('profile.testimonial_video_enabled', false));
});

test('the admin can turn video reviews off', function () {
    $profile = Profile::query()->firstOrFail();

    $this->actingAs(User::factory()->create())->patch(route('admin.profile.update'), [
        ...$profile->only(['name', 'email']),
        'headline' => ['fr' => 'Titre', 'en' => 'Title'],
        'bio_short' => ['fr' => 'Court', 'en' => 'Short'],
        'bio_full' => ['fr' => 'Long', 'en' => 'Long'],
        'testimonial_video_enabled' => '0',
    ])->assertSessionHasNoErrors();

    expect($profile->fresh()->testimonial_video_enabled)->toBeFalse();
});
