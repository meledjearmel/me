<?php

use App\Ai\Agents\PortfolioAssistant;
use App\Enums\ProjectStatus;
use App\Enums\PublicationStatus;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Services\PortfolioKnowledge;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Profile::factory()->create();
    Cache::flush();
});

/** Projet publié « App Station ». */
function appStation(): Project
{
    return Project::factory()->create([
        'title' => ['fr' => 'App Station', 'en' => 'App Station'],
        'slug' => 'app-station',
        'status' => ProjectStatus::Published,
    ]);
}

test('a case study gets the cards of the items mentioned in its story', function () {
    $mentioned = appStation();
    $hidden = Project::factory()->create(['status' => ProjectStatus::Archived]);
    $project = Project::factory()->create([
        'status' => ProjectStatus::Published,
        'context' => ['fr' => "Suite de @[App Station](project:{$mentioned->id}) et de @[Caché](project:{$hidden->id})."],
    ]);

    $this->get("/fr/projects/{$project->slug}")->assertOk()->assertInertia(fn ($page) => $page
        ->where("project.mentions.project:{$mentioned->id}.title", 'App Station')
        ->where("project.mentions.project:{$mentioned->id}.url", route('projects.show', ['locale' => 'fr', 'project' => 'app-station']))
        ->missing("project.mentions.project:{$hidden->id}"));
});

test('an experience and the now page get the cards of their mentions', function () {
    $project = appStation();
    Experience::factory()->create([
        'status' => PublicationStatus::Published,
        'description' => ['fr' => "J'y ai lancé @[App Station](project:{$project->id})."],
    ]);
    SiteSetting::current()->update(['now_content' => ['fr' => "Je travaille sur @[App Station](project:{$project->id})."]]);

    $this->get('/fr/about')->assertInertia(fn ($page) => $page
        ->where("experiences.0.mentions.project:{$project->id}.title", 'App Station'));
    $this->get('/fr/now')->assertInertia(fn ($page) => $page
        ->where("mentions.project:{$project->id}.title", 'App Station'));
});

test('the assistant knowledge keeps only the name of a mention', function () {
    $project = appStation();
    Project::factory()->create([
        'status' => ProjectStatus::Published,
        'context' => ['fr' => "Suite de @[App Station](project:{$project->id})."],
    ]);

    expect(app(PortfolioKnowledge::class)->build('fr'))
        ->toContain('Suite de App Station.')
        ->not->toContain('(project:');
});

test('the assistant reply comes with the cards of the project and article pages it links to', function () {
    $project = appStation();
    $post = Post::factory()->create(['title' => ['fr' => 'Mon article', 'en' => 'My article']]);
    $draft = Post::factory()->draft()->create();
    $projectUrl = route('projects.show', ['locale' => 'fr', 'project' => 'app-station']);
    $postUrl = route('blog.show', ['locale' => 'fr', 'post' => $post->slug]);
    $draftUrl = route('blog.show', ['locale' => 'fr', 'post' => $draft->slug]);
    PortfolioAssistant::fake(["Voyez {$projectUrl}, {$postUrl} et {$draftUrl}."]);

    $mentions = $this->postJson('/fr/chat', ['message' => 'Un projet ?'])->assertOk()->json('mentions');

    expect($mentions[$projectUrl]['title'])->toBe('App Station')
        ->and($mentions[$postUrl]['title'])->toBe('Mon article')
        ->and($mentions)->not->toHaveKey($draftUrl);
});
