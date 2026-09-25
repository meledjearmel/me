<?php

use App\Enums\EngagementStatus;
use App\Enums\EngagementType;
use App\Jobs\SendEngagementMails;
use App\Mail\CvMail;
use App\Mail\EngagementReceivedMail;
use App\Models\Engagement;
use App\Models\Experience;
use App\Models\JobProfile;
use App\Models\ProfessionalReference;
use App\Models\Profile;
use App\Models\User;
use App\Services\CvGenerator;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Profile::factory()->create(['email' => 'owner@example.test']);
    Mail::fake();
});

test('a freelance request is stored and only the owner is notified', function () {
    $this->post('/fr/engagements', [
        'type' => 'freelance',
        'name' => 'Awa Client',
        'email' => 'awa@example.test',
        'company' => 'Awa SARL',
        'subject' => 'Application web',
        'budget_type' => 'fixed',
        'budget_amount' => 5000,
        'budget_currency' => 'CHF',
        'timeline' => 'quarter',
        'message' => 'Nous cherchons un développeur pour une plateforme de réservation.',
    ])->assertRedirect();

    $engagement = Engagement::query()->firstOrFail();

    expect($engagement->type)->toBe(EngagementType::Freelance)
        ->and($engagement->status)->toBe(EngagementStatus::New)
        ->and($engagement->locale)->toBe('fr')
        ->and($engagement->cv_sent_at)->toBeNull()
        ->and($engagement->budget_label)->toBe('5 000 CHF (forfait)');

    Mail::assertSent(EngagementReceivedMail::class, fn ($mail) => $mail->hasTo('owner@example.test'));
    Mail::assertNotSent(CvMail::class);
});

test('a hiring request sends the tailored CV as a PDF to the recruiter', function () {
    $jobProfile = JobProfile::factory()->create();
    Experience::factory()->create();

    $this->post('/en/engagements', [
        'type' => 'hiring',
        'name' => 'Rita Recruiter',
        'email' => 'rita@example.test',
        'company' => 'Talent Corp',
        'subject' => 'Senior Laravel developer',
        'job_profile_id' => $jobProfile->id,
        'contract' => 'cdi',
    ])->assertRedirect();

    $engagement = Engagement::query()->firstOrFail();

    Mail::assertSent(CvMail::class, function (CvMail $mail) {
        return $mail->hasTo('rita@example.test')
            && $mail->engagement->locale === 'en'
            && str_starts_with($mail->pdf, '%PDF')
            && str_ends_with($mail->filename, '.pdf');
    });
    Mail::assertSent(EngagementReceivedMail::class, fn ($mail) => $mail->hasTo('owner@example.test'));
    expect($engagement->fresh()->cv_sent_at)->not->toBeNull();
});

test('a failed CV email does not break the request', function () {
    $jobProfile = JobProfile::factory()->create();
    $engagement = Engagement::factory()->hiring()->create(['job_profile_id' => $jobProfile->id]);

    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    (new SendEngagementMails($engagement->id))->handle(app(CvGenerator::class));

    expect($engagement->fresh()->cv_sent_at)->toBeNull();
});

test('a hiring request needs a company and a profile, a freelance request needs a message', function () {
    $this->post('/fr/engagements', [
        'type' => 'hiring',
        'name' => 'Rita',
        'email' => 'rita@example.test',
        'subject' => 'Développeur',
    ])->assertSessionHasErrors(['company', 'job_profile_id']);

    $this->post('/fr/engagements', [
        'type' => 'freelance',
        'name' => 'Awa',
        'email' => 'awa@example.test',
        'subject' => 'Site web',
    ])->assertSessionHasErrors('message');

    expect(Engagement::query()->count())->toBe(0);
});

test('the honeypot field rejects bots', function () {
    $this->post('/fr/engagements', [
        'type' => 'freelance',
        'name' => 'Bot',
        'email' => 'bot@example.test',
        'subject' => 'Spam',
        'message' => 'Hello',
        'website' => 'https://spam.test',
    ])->assertSessionHasErrors('website');
});

test('the CV generator produces a PDF for a job profile in both languages', function () {
    $jobProfile = JobProfile::factory()->create();
    Experience::factory()->create();

    $generator = app(CvGenerator::class);

    foreach (['fr', 'en'] as $locale) {
        expect($generator->data($jobProfile, $locale)['locale'])->toBe($locale)
            ->and(str_starts_with($generator->pdf($jobProfile, $locale), '%PDF'))->toBeTrue();
    }
});

test('the CV profiles are shared with every public page', function () {
    JobProfile::factory()->count(2)->create();

    $this->get('/fr/contact')->assertInertia(fn ($page) => $page->has('cvProfiles', 2));
});

test('only authenticated users can manage engagements', function () {
    $engagement = Engagement::factory()->create();

    $this->get(route('admin.engagements.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.engagements.update', $engagement))
        ->assertRedirect();

    expect($engagement->fresh()->status)->toBe(EngagementStatus::Handled);

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.engagements.destroy', $engagement))
        ->assertRedirect(route('admin.engagements.index'));

    expect(Engagement::query()->count())->toBe(0);
});

test('the CV lists only the references marked for the CV, limited to their allowed fields', function () {
    ProfessionalReference::factory()->create([
        'name' => 'Marc Manager', 'role' => 'CTO', 'company' => 'Acme', 'email' => 'marc@acme.test',
        'phone' => '+41 79 000 00 00', 'is_public' => true, 'visible_fields' => ['name', 'role', 'company'],
        'notes' => 'Note privée',
    ]);
    ProfessionalReference::factory()->create(['name' => 'Caché', 'is_public' => false]);

    $references = app(CvGenerator::class)->data(JobProfile::factory()->create(), 'fr')['references'];

    expect($references)->toHaveCount(1)
        ->and($references[0])->toBe(['name' => 'Marc Manager', 'role' => 'CTO', 'company' => 'Acme']);
});

test('the CV has no references section when none is marked for the CV', function () {
    ProfessionalReference::factory()->create(['is_public' => false]);

    expect(app(CvGenerator::class)->data(JobProfile::factory()->create(), 'fr')['references'])->toBe([]);
});

test('a period budget needs its unit and shows the rate per period', function () {
    $payload = [
        'type' => 'freelance', 'name' => 'Awa', 'email' => 'awa@example.test',
        'subject' => 'Application web', 'message' => 'Besoin de renfort sur un projet.',
        'budget_type' => 'period', 'budget_amount' => 80, 'budget_currency' => 'EUR',
    ];

    $this->post('/fr/engagements', $payload)->assertSessionHasErrors('budget_period');

    $this->post('/fr/engagements', [...$payload, 'budget_period' => 'hour'])->assertSessionHasNoErrors();

    expect(Engagement::query()->firstOrFail()->budget_label)->toBe('80 EUR / heure');
});

test('without an amount the budget is dropped entirely', function () {
    $this->post('/fr/engagements', [
        'type' => 'freelance', 'name' => 'Awa', 'email' => 'awa@example.test',
        'subject' => 'Site web', 'message' => 'Un site vitrine.',
        'budget_type' => 'period', 'budget_amount' => '', 'budget_currency' => 'CHF', 'budget_period' => 'day',
    ])->assertSessionHasNoErrors();

    $engagement = Engagement::query()->firstOrFail();

    expect($engagement->budget_label)->toBeNull()
        ->and($engagement->budget_type)->toBeNull()
        ->and($engagement->budget_period)->toBeNull();
});

test('a fixed budget ignores any period sent along', function () {
    $this->post('/fr/engagements', [
        'type' => 'freelance', 'name' => 'Awa', 'email' => 'awa@example.test',
        'subject' => 'Site web', 'message' => 'Un site vitrine.',
        'budget_type' => 'fixed', 'budget_amount' => 3000, 'budget_currency' => 'XOF', 'budget_period' => 'month',
    ])->assertSessionHasNoErrors();

    $engagement = Engagement::query()->firstOrFail();

    expect($engagement->budget_period)->toBeNull()
        ->and($engagement->budget_label)->toBe('3 000 FCFA (forfait)');
});
