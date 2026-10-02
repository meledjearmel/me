<?php

use App\Enums\CvSource;
use App\Enums\PublicationStatus;
use App\Jobs\SendPushNotification;
use App\Models\CvDownload;
use App\Models\JobProfile;
use App\Models\Profile;
use App\Models\User;
use App\Services\GeoLocator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/** Un navigateur ordinaire : les robots ne sont pas comptés. */
const BROWSER = ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/140.0 Safari/537.36'];

beforeEach(function () {
    Queue::fake();
    $this->profile = Profile::factory()->create(['email' => 'owner@example.test', 'cv_source' => CvSource::Uploaded]);
    $this->jobProfile = JobProfile::factory()->create();
});

function uploadCv(JobProfile $jobProfile, string $locale = 'fr'): void
{
    Storage::fake('public');
    $jobProfile->addMedia(UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 uploaded'))
        ->toMediaCollection(JobProfile::cvFileCollection($locale));
}

test('the contact page offers the CV only when a job profile is published', function () {
    $this->get('/fr/contact')->assertInertia(fn ($page) => $page->where('cvAvailable', true));

    $this->jobProfile->update(['status' => PublicationStatus::Draft]);

    $this->get('/fr/contact')->assertInertia(fn ($page) => $page->where('cvAvailable', false));
    $this->withHeaders(BROWSER)->post('/fr/cv')->assertNotFound();
});

test('a download serves the PDF and records where the visitor came from', function () {
    uploadCv($this->jobProfile);
    $this->mock(GeoLocator::class)->shouldReceive('locate')->andReturn([
        'country_code' => 'CI', 'country' => "Côte d'Ivoire", 'city' => 'Abidjan',
    ]);

    $this->withHeaders([...BROWSER, 'Referer' => 'https://www.linkedin.com/in/someone'])->get('/fr?utm_campaign=profil');

    $response = $this->withHeaders(BROWSER)->post('/fr/cv', ['email' => 'rh@example.test']);

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toBe('%PDF-1.4 uploaded')
        ->and($response->headers->get('Content-Disposition'))->toStartWith('attachment; filename="CV-');

    $download = CvDownload::query()->sole();
    expect($download->source)->toBe(CvSource::Uploaded)
        ->and($download->locale)->toBe('fr')
        ->and($download->email)->toBe('rh@example.test')
        ->and($download->referrer_host)->toBe('linkedin.com')
        ->and($download->utm_campaign)->toBe('profil')
        ->and($download->city)->toBe('Abidjan')
        ->and($download->device)->toBe('desktop')
        ->and($download->job_profile_id)->toBe($this->jobProfile->id);

    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->data['type'] === 'cv_download'
        && str_contains($job->body, "Abidjan, Côte d'Ivoire")
        && str_contains($job->body, 'linkedin.com'));
});

test('a ref link on the home redirect is kept as the campaign source', function () {
    $this->withHeaders([...BROWSER, 'Accept-Language' => 'fr-FR'])->get('/?ref=LinkedIn')->assertRedirect('/fr?ref=LinkedIn');
    $this->withHeaders(BROWSER)->get('/fr?ref=LinkedIn');
    $this->withHeaders(BROWSER)->post('/fr/cv');

    expect(CvDownload::query()->sole()->utm_source)->toBe('linkedin');
});

test('the generated CV is served when it has priority or when nothing is uploaded', function () {
    $this->withHeaders(BROWSER)->post('/en/cv')->assertOk();
    expect(CvDownload::query()->latest('id')->first()->source)->toBe(CvSource::Generated);

    uploadCv($this->jobProfile, 'en');
    $this->profile->update(['cv_source' => CvSource::Generated]);
    CvDownload::query()->delete();

    $response = $this->withHeaders(BROWSER)->post('/en/cv')->assertOk();
    expect($response->getContent())->toStartWith('%PDF')->not->toBe('%PDF-1.4 uploaded')
        ->and(CvDownload::query()->sole()->source)->toBe(CvSource::Generated);
});

test('the job profile chosen in the admin is the one served', function () {
    $chosen = JobProfile::factory()->create(['sort_order' => 99]);
    $this->profile->update(['cv_job_profile_id' => $chosen->id]);

    $this->withHeaders(BROWSER)->post('/fr/cv')->assertOk();

    expect(CvDownload::query()->sole()->job_profile_id)->toBe($chosen->id);
});

test('robots download without being counted', function () {
    $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->post('/fr/cv')->assertOk();

    expect(CvDownload::query()->count())->toBe(0);
    Queue::assertNotPushed(SendPushNotification::class);
});

test('a second download the same day is not counted twice but keeps a late email', function () {
    $this->withHeaders(BROWSER)->post('/fr/cv')->assertOk();
    $this->withHeaders(BROWSER)->post('/fr/cv', ['email' => 'late@example.test'])->assertOk();

    expect(CvDownload::query()->sole()->email)->toBe('late@example.test');
    Queue::assertPushed(SendPushNotification::class, 1);
});

test('the email must be valid and the honeypot must stay empty', function () {
    $this->withHeaders(BROWSER)->postJson('/fr/cv', ['email' => 'not-an-email'])->assertJsonValidationErrors('email');
    $this->withHeaders(BROWSER)->postJson('/fr/cv', ['website' => 'spam'])->assertJsonValidationErrors('website');

    expect(CvDownload::query()->count())->toBe(0);
});

test('the location stays empty without a GeoLite2 database', function () {
    config(['services.maxmind.database' => storage_path('app/geoip/missing.mmdb')]);

    expect(app(GeoLocator::class)->locate('8.8.8.8'))->toBe(['country_code' => null, 'country' => null, 'city' => null])
        ->and(app(GeoLocator::class)->locate('127.0.0.1'))->toBe(['country_code' => null, 'country' => null, 'city' => null]);
});

test('the admin lists and shows downloads, and saves the CV settings', function () {
    $user = User::factory()->create();
    $download = CvDownload::factory()->withEmail()->create(['job_profile_id' => $this->jobProfile->id]);

    $this->get(route('admin.cv-downloads.index'))->assertRedirect(route('login'));

    $this->actingAs($user)->get(route('admin.cv-downloads.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/cv-downloads/index')
            ->has('downloads.data', 1)
            ->where('summary.with_email', 1)
        );
    $this->actingAs($user)->get(route('admin.cv-downloads.show', $download))
        ->assertInertia(fn ($page) => $page->where('download.email', $download->email));

    $this->actingAs($user)->patch(route('admin.profile.update'), [
        ...$this->profile->only(['name', 'email']),
        'headline' => ['fr' => 'Titre', 'en' => 'Title'],
        'bio_short' => ['fr' => 'Court', 'en' => 'Short'],
        'bio_full' => ['fr' => 'Long', 'en' => 'Long'],
        'cv_job_profile_id' => $this->jobProfile->id,
        'cv_source' => 'generated',
    ])->assertSessionHasNoErrors();

    expect($this->profile->fresh())
        ->cv_job_profile_id->toBe($this->jobProfile->id)
        ->cv_source->toBe(CvSource::Generated);
});

test('the dashboard and the API report the downloads', function () {
    CvDownload::factory()->count(2)->create(['country' => 'France', 'referrer_host' => 'linkedin.com', 'utm_source' => null]);
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/cv-downloads')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.origin', 'linkedin.com')
        ->assertJsonMissingPath('data.0.visitor_hash');

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('cv_downloads.period', 2)
        ->assertJsonPath('cv_downloads.by_country.0', ['label' => 'France', 'count' => 2]);
});
