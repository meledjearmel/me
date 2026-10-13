<?php

use App\Ai\Agents\CvTailorWriter;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\ListJobApplicationsTool;
use App\Mcp\Tools\SaveJobApplicationTool;
use App\Models\Domain;
use App\Models\Experience;
use App\Models\ExperienceHighlight;
use App\Models\JobApplication;
use App\Models\JobProfile;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Services\CvGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Queue::fake();
    Profile::factory()->create();
    $this->user = User::factory()->create();
    $this->jobProfile = JobProfile::factory()->create();
    $this->experience = Experience::factory()->create();
    $this->first = ExperienceHighlight::factory()->create(['experience_id' => $this->experience->id, 'text' => ['fr' => 'Équipe de 6 encadrée', 'en' => 'Led a team of 6'], 'sort_order' => 1]);
    $this->second = ExperienceHighlight::factory()->create(['experience_id' => $this->experience->id, 'text' => ['fr' => 'API Laravel en production', 'en' => 'Laravel API in production'], 'sort_order' => 2]);
    $this->third = ExperienceHighlight::factory()->create(['experience_id' => $this->experience->id, 'text' => ['fr' => 'Formation des stagiaires', 'en' => 'Trained interns'], 'sort_order' => 3]);
    $domain = Domain::factory()->create();
    $this->react = Skill::factory()->create(['domain_id' => $domain->id, 'name' => ['fr' => 'React', 'en' => 'React'], 'sort_order' => 1]);
    $this->laravel = Skill::factory()->create(['domain_id' => $domain->id, 'name' => ['fr' => 'Laravel', 'en' => 'Laravel'], 'sort_order' => 2]);
    $this->project = Project::factory()->create();
    $this->application = JobApplication::factory()->create(['job_profile_id' => $this->jobProfile->id, 'job_posting_text' => 'Nous cherchons un lead Laravel.']);
    $this->application->syncProjects([$this->project->id]);
});

test('a tailored CV keeps only the chosen highlights, in order, and puts the chosen skills first', function () {
    $cv = app(CvGenerator::class)->data($this->jobProfile, 'fr', [
        'title' => 'Lead développeur Laravel',
        'summary' => 'Dix ans de Laravel.',
        'experiences' => [$this->experience->id => [$this->second->id, $this->first->id]],
        'skill_ids' => [$this->laravel->id],
        'project_ids' => [$this->project->id],
    ]);

    expect($cv['title'])->toBe('Lead développeur Laravel')
        ->and($cv['summary'])->toBe('Dix ans de Laravel.')
        ->and($cv['experiences'][0]['highlights'])->toBe(['API Laravel en production', 'Équipe de 6 encadrée'])
        ->and($cv['skills'][0]['items'])->toBe(['Laravel', 'React'])
        ->and($cv['projects'][0]['title'])->toBe($this->project->getTranslation('title', 'fr'));

    $standard = app(CvGenerator::class)->data($this->jobProfile, 'fr');
    expect($standard['experiences'][0]['highlights'])->toHaveCount(3)
        ->and($standard['skills'][0]['items'])->toBe(['React', 'Laravel']);
});

test('the AI tailors the CV from the posting, keeping only existing highlights and skills', function () {
    CvTailorWriter::fake([json_encode([
        'title' => 'Lead Laravel',
        'summary' => 'Expert Laravel (id 3) qui encadre des équipes.',
        'experiences' => [['experience_id' => $this->experience->id, 'highlight_ids' => [$this->second->id, 999, $this->first->id]], ['experience_id' => 999, 'highlight_ids' => [1]]],
        'skill_ids' => [$this->laravel->id, 999],
        'reasoning' => 'L’offre insiste sur Laravel.',
    ])]);

    $this->actingAs($this->user)->post(route('admin.job-applications.cv.tailor', $this->application))->assertRedirect();

    $tailoring = $this->application->refresh()->cv_tailoring;

    expect($tailoring['summary'])->toBe('Expert Laravel qui encadre des équipes.')
        ->and($tailoring['experiences'])->toBe([(string) $this->experience->id => [$this->second->id, $this->first->id]])
        ->and($tailoring['skill_ids'])->toBe([$this->laravel->id])
        ->and($tailoring['project_ids'])->toBe([$this->project->id])
        ->and($this->application->cv_tailored_at)->not->toBeNull();

    CvTailorWriter::assertPrompted(fn ($prompt) => $prompt->contains('lead Laravel') && $prompt->contains("{$this->second->id} : API Laravel en production"));
});

test('a tailored CV replaces the uploaded one on the application page, and can be reset', function () {
    Storage::fake('public');
    $this->jobProfile->addMedia(UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 uploaded'))
        ->toMediaCollection(JobProfile::cvFileCollection('fr'));
    $url = "/fr/for/{$this->application->token}/cv";
    $browser = ['User-Agent' => 'Mozilla/5.0 Safari/605.1.15'];

    expect($this->withHeaders($browser)->get($url)->getContent())->toBe('%PDF-1.4 uploaded');

    $this->application->forceFill(['cv_tailoring' => ['title' => 'Lead Laravel', 'summary' => 'Résumé.', 'experiences' => [], 'skill_ids' => []]])->save();
    $tailored = $this->withHeaders($browser)->get($url)->getContent();
    expect($tailored)->toStartWith('%PDF')->not->toBe('%PDF-1.4 uploaded');

    $this->actingAs($this->user)->put(route('admin.job-applications.cv.update', $this->application), ['title' => 'Tech lead', 'summary' => 'Nouveau résumé.'])->assertRedirect();
    expect($this->application->refresh()->cv_tailoring['title'])->toBe('Tech lead');

    $this->actingAs($this->user)->get(route('admin.job-applications.cv.show', $this->application))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->actingAs($this->user)->delete(route('admin.job-applications.cv.destroy', $this->application))->assertRedirect();
    expect($this->application->refresh()->cv_tailoring)->toBeNull();
});

test('the CV is tailored through the API and MCP', function () {
    Sanctum::actingAs($this->user);
    CvTailorWriter::fake([json_encode(['title' => 'Lead Laravel', 'summary' => 'Résumé.', 'experiences' => [], 'skill_ids' => []])]);

    $this->postJson("/api/v1/job-applications/{$this->application->id}/cv/tailor")->assertOk()->assertJsonPath('cv_tailoring.title', 'Lead Laravel');
    $this->deleteJson("/api/v1/job-applications/{$this->application->id}/cv")->assertOk()->assertJsonPath('cv_tailoring', null);

    $draft = JobApplication::factory()->draft()->create(['job_profile_id' => $this->jobProfile->id]);
    $server = PortfolioServer::actingAs($this->user);
    $server->tool(ListJobApplicationsTool::class, ['id' => $draft->id])->assertOk()->assertSee('API Laravel en production');
    $server->tool(SaveJobApplicationTool::class, [
        'id' => $draft->id,
        'cv_title' => 'Lead Laravel',
        'cv_experiences' => [['experience_id' => $this->experience->id, 'highlight_ids' => [$this->third->id, 12345]]],
        'cv_skill_ids' => [$this->react->id],
    ])->assertOk();

    expect($draft->refresh()->cv_tailoring['experiences'])->toBe([(string) $this->experience->id => [$this->third->id]])
        ->and($draft->cv_tailoring['skill_ids'])->toBe([$this->react->id]);
});
