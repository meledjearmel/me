<?php

use App\Ai\Agents\ServiceWriter;
use App\Enums\ServicePriceMode;
use App\Enums\TestimonialStatus;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\SaveContentDraftTool;
use App\Models\EstimatorItem;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\GeoLocator;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Profile::factory()->create();
    SiteSetting::factory()->estimating()->create();
    $this->user = User::factory()->create();
});

/** @return array<string, mixed> */
function servicePayload(array $overrides = []): array
{
    return [
        'slug' => 'application-mobile',
        'title' => ['fr' => 'Application mobile', 'en' => 'Mobile app'],
        'tagline' => ['fr' => 'Vos clients dans leur poche.', 'en' => null],
        'description' => ['fr' => null, 'en' => null],
        'includes' => ['fr' => "Design\nPublication", 'en' => null],
        'price_mode' => 'quote',
        'status' => 'published',
        'sort_order' => 0,
        ...$overrides,
    ];
}

test('the services page shows published offers with their price, projects and testimonial', function () {
    $projectType = EstimatorItem::factory()->create();
    $project = Project::factory()->create();
    Testimonial::factory()->create(['project_id' => $project->id, 'status' => TestimonialStatus::Approved, 'highlight' => ['fr' => 'Un vrai partenaire.']]);

    $estimated = Service::factory()->create(['price_mode' => ServicePriceMode::Estimator, 'estimator_item_id' => $projectType->id, 'sort_order' => 1]);
    $estimated->projects()->attach($project);
    Service::factory()->manual(['EUR' => 1900])->create(['sort_order' => 2]);
    Service::factory()->draft()->create();

    // 30 h × 1,2 de marge × 100 CHF/h : 3 600 CHF ; le montant saisi en EUR garde sa devise.
    $this->get('/fr/services')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/services')
            ->has('services', 2)
            ->where('services.0.price', ['amount' => 3600, 'currency' => 'CHF', 'billing' => 'fixed'])
            ->where('services.0.estimate_type_id', $projectType->id)
            ->where('services.0.includes', ['Design sur mesure', 'Referencement de base', 'Mise en ligne'])
            ->where('services.0.projects.0.slug', $project->slug)
            ->where('services.0.testimonial.highlight', 'Un vrai partenaire.')
            ->where('services.1.price', ['amount' => 1900, 'currency' => 'EUR', 'billing' => null])
            ->where('services.1.estimate_type_id', null));
});

test('an hourly project type shows its hourly rate in the visitor currency', function () {
    $projectType = EstimatorItem::factory()->hourly()->create();
    Service::factory()->create(['price_mode' => ServicePriceMode::Estimator, 'estimator_item_id' => $projectType->id]);
    $this->mock(GeoLocator::class)->shouldReceive('locate')->andReturn(['country_code' => 'CI', 'country' => null, 'city' => null]);

    $this->get('/fr/services')->assertInertia(fn ($page) => $page
        ->where('services.0.price', ['amount' => 30000, 'currency' => 'XOF', 'billing' => 'hourly']));
});

test('the services page is hidden without a published offer', function () {
    Service::factory()->draft()->create();

    $this->get('/fr/services')->assertNotFound();
});

test('an offer link preselects its project type in the estimator', function () {
    $projectType = EstimatorItem::factory()->create();

    $this->get("/fr/estimate?type={$projectType->id}")
        ->assertInertia(fn ($page) => $page->where('preselectedTypeId', $projectType->id));
    $this->get('/fr/estimate?type=999')
        ->assertInertia(fn ($page) => $page->where('preselectedTypeId', null));
});

test('an offer is managed in the admin, english required to publish', function () {
    $project = Project::factory()->create();

    $this->actingAs($this->user)->get(route('admin.services.index'))->assertOk();
    $this->actingAs($this->user)->get(route('admin.services.create'))->assertOk();

    $this->actingAs($this->user)
        ->post(route('admin.services.store'), servicePayload(['title' => ['fr' => 'Application mobile', 'en' => null]]))
        ->assertSessionHasErrors('title.en');

    $this->actingAs($this->user)
        ->post(route('admin.services.store'), servicePayload(['project_ids' => [$project->id]]))
        ->assertRedirect(route('admin.services.index'));

    $service = Service::query()->firstOrFail();

    expect($service->projects->modelKeys())->toBe([$project->id])
        ->and($service->includesList('fr'))->toBe(['Design', 'Publication']);

    $this->actingAs($this->user)->get(route('admin.services.edit', $service))->assertOk();
});

test('a manual price needs an amount and an estimator price needs a project type', function () {
    $this->actingAs($this->user)
        ->post(route('admin.services.store'), servicePayload(['price_mode' => 'manual', 'manual_prices' => ['CHF' => '']]))
        ->assertSessionHasErrors('manual_prices');

    $this->actingAs($this->user)
        ->post(route('admin.services.store'), servicePayload(['price_mode' => 'estimator']))
        ->assertSessionHasErrors('estimator_item_id');
});

test('the AI drafts the french text of an offer', function () {
    ServiceWriter::fake([json_encode([
        'title' => 'Boutique en ligne',
        'slug' => 'Boutique En Ligne!',
        'tagline' => 'Vendre en ligne sans prise de tête.',
        'description' => 'Pour les commerçants qui veulent vendre en ligne.',
        'includes' => ['Catalogue', '', 'Paiement'],
    ])]);

    $this->actingAs($this->user)
        ->postJson(route('admin.ai.draft-service'), ['notes' => 'Boutiques Shopify ou Laravel pour commerçants.'])
        ->assertOk()
        ->assertJsonPath('service.slug', 'boutique-en-ligne')
        ->assertJsonPath('service.includes', "Catalogue\nPaiement");
});

test('offers are managed through the API and drafted through MCP', function () {
    Sanctum::actingAs($this->user);

    $id = $this->postJson('/api/v1/services', servicePayload())->assertCreated()->assertJsonPath('price_mode', 'quote')->json('id');
    $this->getJson('/api/v1/services')->assertOk()->assertJsonCount(1, 'data');
    $this->patchJson("/api/v1/status/service/{$id}", ['status' => 'draft'])->assertOk();

    PortfolioServer::actingAs($this->user)
        ->tool(SaveContentDraftTool::class, ['type' => 'service', 'fields' => [
            'slug' => 'application-mobile',
            'title' => 'Doublon',
            'price_mode' => 'quote',
        ]])
        ->assertHasErrors();

    PortfolioServer::actingAs($this->user)
        ->tool(SaveContentDraftTool::class, ['type' => 'service', 'fields' => [
            'slug' => 'site-vitrine',
            'title' => 'Site vitrine',
            'includes' => "Design\nMise en ligne",
            'price_mode' => 'quote',
        ]])
        ->assertOk();

    expect(Service::query()->where('slug', 'site-vitrine')->firstOrFail()->status->value)->toBe('draft');
});
