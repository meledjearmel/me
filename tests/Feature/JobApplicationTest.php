<?php

use App\Ai\Agents\JobApplicationWriter;
use App\Enums\JobApplicationStatus;
use App\Jobs\SendPushNotification;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\ListJobApplicationsTool;
use App\Mcp\Tools\SaveJobApplicationTool;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\JobProfile;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use App\Services\GeoLocator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/** Un navigateur ordinaire : les robots ne sont pas comptés. */
const RECRUITER_BROWSER = ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) Safari/605.1.15'];

beforeEach(function () {
    Queue::fake();
    Profile::factory()->create();
    $this->user = User::factory()->create();
    $this->jobProfile = JobProfile::factory()->create();
    $this->project = Project::factory()->create();
    $this->application = JobApplication::factory()->create(['job_profile_id' => $this->jobProfile->id]);
    $this->application->syncProjects([$this->project->id]);
});

/** @return array<string, mixed> */
function jobApplicationPayload(array $overrides = []): array
{
    return [
        'company' => 'Globex',
        'contact_name' => null,
        'position' => 'Développeur Laravel',
        'locale' => 'fr',
        'intro' => 'Bonjour, voici mes projets.',
        'status' => 'active',
        ...$overrides,
    ];
}

test('the token reads the company name and cannot be guessed', function () {
    expect($this->application->token)->toMatch('/^acme-sa-[a-z0-9]{6}$/');
});

test('an open application page shows its intro and projects, never indexed', function () {
    $this->application->update(['intro' => "Voyez @[{$this->project->title}](project:{$this->project->id})."]);

    $this->withHeaders(RECRUITER_BROWSER)->get("/fr/for/{$this->application->token}")
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertInertia(fn ($page) => $page
            ->component('public/job-application')
            ->where('application.company', 'Acme SA')
            ->where('application.has_cv', true)
            ->where("application.mentions.project:{$this->project->id}.title", $this->project->title)
            ->where('projects.0.slug', $this->project->slug));
});

test('a draft, archived or expired page answers 404, and the other language redirects', function () {
    $draft = JobApplication::factory()->draft()->create();
    $archived = JobApplication::factory()->archived()->create();
    $expired = JobApplication::factory()->create(['expires_on' => now()->subDay()->toDateString()]);

    $this->get("/fr/for/{$draft->token}")->assertNotFound();
    $this->get("/fr/for/{$archived->token}")->assertNotFound();
    $this->get("/fr/for/{$expired->token}")->assertNotFound();
    $this->get('/fr/for/inconnu-abcdef')->assertNotFound();
    $this->get("/en/for/{$this->application->token}")->assertRedirect("/fr/for/{$this->application->token}");
});

test('a visit is counted once a day, without bots or my own visits, and the first one notifies me', function () {
    $this->mock(GeoLocator::class)->shouldReceive('locate')->andReturn(['country_code' => 'CH', 'country' => 'Suisse', 'city' => 'Genève']);
    $url = "/fr/for/{$this->application->token}";

    $this->withHeaders(RECRUITER_BROWSER)->get($url);
    $this->withHeaders(RECRUITER_BROWSER)->get($url);
    $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->get($url);
    $this->actingAs($this->user)->withHeaders(RECRUITER_BROWSER)->get($url);

    $application = $this->application->refresh();

    expect($application->views_count)->toBe(1)
        ->and($application->first_viewed_at)->not->toBeNull()
        ->and($application->events()->sole()->city)->toBe('Genève');

    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->title === 'Acme SA a ouvert ta candidature');
});

test('the CV of the chosen profile is downloaded and counted', function () {
    Storage::fake('public');
    $this->jobProfile->addMedia(UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 cv'))
        ->toMediaCollection(JobProfile::cvFileCollection('fr'));

    $response = $this->withHeaders(RECRUITER_BROWSER)->get("/fr/for/{$this->application->token}/cv");

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toBe('%PDF-1.4 cv')
        ->and($this->application->refresh()->cv_downloads_count)->toBe(1)
        ->and($this->application->events()->where('type', JobApplicationEvent::CV_DOWNLOAD)->count())->toBe(1);
});

test('application pages are managed in the admin with their projects in order', function () {
    $second = Project::factory()->create();

    $this->actingAs($this->user)->get(route('admin.job-applications.index'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.job-applications.create'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.job-applications.show', $this->application))->assertOk();
    $this->actingAs($this->user)->get(route('admin.job-applications.edit', $this->application))->assertOk();

    $this->actingAs($this->user)
        ->post(route('admin.job-applications.store'), jobApplicationPayload(['project_ids' => [$second->id, $this->project->id]]))
        ->assertRedirect();

    $created = JobApplication::query()->where('company', 'Globex')->sole();

    expect($created->projects->modelKeys())->toBe([$second->id, $this->project->id])
        ->and($created->status)->toBe(JobApplicationStatus::Active);
});

test('the AI prepares the page from a job posting, keeping only published projects and profiles', function () {
    JobApplicationWriter::fake([json_encode([
        'company' => 'Initech',
        'contact_name' => 'Bill',
        'position' => 'Lead développeur',
        'intro' => "J'ai conçu @[{$this->project->title}](project:{$this->project->id}), puis Registra (id 2) et @[Fantôme](project:999).",
        'project_ids' => [$this->project->id, 999],
        'job_profile_id' => $this->jobProfile->id,
        'reasoning' => 'Projet Laravel le plus proche.',
    ])]);

    $this->actingAs($this->user)
        ->postJson(route('admin.ai.draft-job-application'), [
            'job_posting' => str_repeat('Nous cherchons un développeur Laravel expérimenté. ', 3),
            'locale' => 'fr',
        ])
        ->assertOk()
        ->assertJsonPath('application.company', 'Initech')
        // Le jeton valide reste un lien ; l'identifiant égaré et la mention inconnue disparaissent.
        ->assertJsonPath('application.intro', "J'ai conçu @[{$this->project->title}](project:{$this->project->id}), puis Registra et Fantôme.")
        ->assertJsonPath('application.project_ids', [$this->project->id]);

    // L'IA reçoit le jeton à recopier pour chaque projet.
    JobApplicationWriter::assertPrompted(fn ($prompt) => $prompt->contains('développeur Laravel expérimenté')
        && $prompt->contains("@[{$this->project->title}](project:{$this->project->id})"));
});

test('application pages are managed through the API and prepared as drafts through MCP', function () {
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/job-applications')->assertOk()->assertJsonPath('data.0.views_count', 0);
    $this->getJson("/api/v1/job-applications/{$this->application->id}/events")->assertOk()->assertJsonCount(0, 'data');
    $this->postJson('/api/v1/job-applications', jobApplicationPayload())->assertCreated()->assertJsonPath('is_open', true);

    $server = PortfolioServer::actingAs($this->user);
    $server->tool(SaveJobApplicationTool::class, [
        'company' => 'Hooli',
        'position' => 'Développeur mobile',
        'locale' => 'en',
        'intro' => 'Hello, here are my projects.',
        'project_ids' => [$this->project->id],
    ])->assertOk();

    $draft = JobApplication::query()->where('company', 'Hooli')->sole();
    expect($draft->status)->toBe(JobApplicationStatus::Draft)
        ->and($draft->drafted_by)->not->toBeNull();

    $server->tool(SaveJobApplicationTool::class, ['id' => $this->application->id, 'position' => 'Autre'])->assertHasErrors();
    $server->tool(ListJobApplicationsTool::class)->assertOk()->assertSee('Hooli');
});
