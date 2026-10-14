<?php

use App\Ai\Agents\CommercialDocumentMailWriter;
use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use App\Enums\EngagementOutcome;
use App\Mail\CommercialDocumentMail;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\DraftQuoteTool;
use App\Mcp\Tools\ListCommercialDocumentsTool;
use App\Models\CommercialDocument;
use App\Models\Engagement;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CommercialDocuments;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Profile::factory()->create(['email' => 'owner@example.test']);
    SiteSetting::factory()->create(['billing_business_name' => 'Armel Dev', 'billing_deposit_percent' => 40, 'billing_quote_validity_days' => 15]);
    Mail::fake();
    Queue::fake();
    $this->user = User::factory()->create();
    $this->engagement = Engagement::factory()->create([
        'name' => 'Awa Koné',
        'email' => 'awa@example.test',
        'subject' => 'Boutique en ligne',
        'estimate' => [
            'currency' => 'CHF', 'price_min' => 4000, 'price_max' => 6000, 'hours_min' => 40, 'hours_max' => 60, 'weeks_min' => 2, 'weeks_max' => 3, 'features' => ['Paiement'],
            'lines' => [
                ['label' => 'Boutique en ligne', 'hours_min' => 36, 'hours_max' => 48, 'rate' => 100],
                ['label' => 'Paiement', 'hours_min' => 12, 'hours_max' => 18, 'rate' => 120],
            ],
        ],
    ]);
});

test('a quote draft is prefilled from the request and its estimate lines', function () {
    $this->actingAs($this->user)
        ->post(route('admin.engagements.documents.store', $this->engagement), ['type' => 'quote'])
        ->assertRedirect();

    $document = CommercialDocument::query()->firstOrFail();

    expect($document->status)->toBe(CommercialDocumentStatus::Draft)
        ->and($document->number)->toBeNull()
        ->and($document->client_email)->toBe('awa@example.test')
        ->and($document->currency)->toBe('CHF')
        // 48 h × 100 + 18 h × 120.
        ->and($document->total)->toBe(6960)
        ->and($document->deposit_percent)->toBe(40)
        ->and($document->valid_until->toDateString())->toBe(now()->addDays(15)->toDateString());

    $this->actingAs($this->user)->get(route('admin.commercial-documents.edit', $document))->assertOk();
    $this->actingAs($this->user)->get(route('admin.commercial-documents.show', $document))->assertOk();
});

test('issuing numbers each type continuously per year, then freezes the document', function () {
    $documents = app(CommercialDocuments::class);
    $first = $documents->issue(CommercialDocument::factory()->create());
    $second = $documents->issue(CommercialDocument::factory()->create());
    $invoice = $documents->issue(CommercialDocument::factory()->invoice()->create());
    $year = now()->year;

    expect($first->number)->toBe("ADEV-DEV-{$year}-001")
        ->and($second->number)->toBe("ADEV-DEV-{$year}-002")
        ->and($invoice->number)->toBe("ADEV-FAC-{$year}-001");

    $this->actingAs($this->user)
        ->put(route('admin.commercial-documents.update', $first), ['locale' => 'fr', 'client_name' => 'X', 'title' => 'X', 'currency' => 'CHF', 'deposit_percent' => 0, 'lines' => [['label' => 'X', 'quantity' => 1, 'unit' => 'flat', 'unit_price' => 1]]])
        ->assertForbidden();
    $this->actingAs($this->user)->delete(route('admin.commercial-documents.destroy', $first))->assertForbidden();
});

test('a draft is created and edited with its lines, and the total follows them', function () {
    $this->actingAs($this->user)->get(route('admin.commercial-documents.create', ['type' => 'invoice']))->assertOk();

    $this->actingAs($this->user)->post(route('admin.commercial-documents.store'), [
        'type' => 'invoice',
        'locale' => 'en',
        'client_name' => 'Acme',
        'title' => 'Maintenance',
        'currency' => 'EUR',
        'deposit_percent' => 0,
        'lines' => [
            ['label' => 'Maintenance', 'quantity' => '2.5', 'unit' => 'hour', 'unit_price' => 90],
            ['label' => 'Hosting', 'quantity' => 1, 'unit' => 'flat', 'unit_price' => 200],
        ],
    ])->assertRedirect();

    $document = CommercialDocument::query()->firstOrFail();

    expect($document->type)->toBe(CommercialDocumentType::Invoice)
        ->and($document->total)->toBe(425);
});

test('sending issues the document and mails the PDF with the link to accept it', function () {
    $document = app(CommercialDocuments::class)->draftFromEngagement($this->engagement);

    $this->actingAs($this->user)
        ->post(route('admin.commercial-documents.send', $document), ['subject' => 'Votre devis', 'message' => "Bonjour,\n\nVoici le devis."])
        ->assertRedirect();

    $document->refresh();

    expect($document->number)->not->toBeNull()
        ->and($document->sent_at)->not->toBeNull();

    Mail::assertSent(CommercialDocumentMail::class, fn (CommercialDocumentMail $mail): bool => $mail->hasTo('awa@example.test')
        && $mail->acceptUrl === route('commercial-documents.show', ['locale' => 'fr', 'token' => $document->token])
        && str_starts_with($mail->pdf, '%PDF'));
});

test('the AI proposes the subject and message of the email', function () {
    CommercialDocumentMailWriter::fake([json_encode(['subject' => 'Votre devis pour la boutique', 'message' => 'Bonjour Awa, voici le devis.'])]);
    $document = app(CommercialDocuments::class)->draftFromEngagement($this->engagement);

    $this->actingAs($this->user)
        ->postJson(route('admin.commercial-documents.suggest-mail', $document))
        ->assertOk()
        ->assertJsonPath('subject', 'Votre devis pour la boutique');
});

test('the client accepts the quote from its link, which wins the request', function () {
    $documents = app(CommercialDocuments::class);
    $document = $documents->issue($documents->draftFromEngagement($this->engagement));

    $this->get(route('commercial-documents.show', ['locale' => 'fr', 'token' => $document->token]))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertInertia(fn ($page) => $page->component('public/commercial-document')->where('document.can_answer', true));
    $this->get(route('commercial-documents.pdf', ['locale' => 'fr', 'token' => $document->token]))->assertOk();

    $this->post(route('commercial-documents.accept', ['locale' => 'fr', 'token' => $document->token]))->assertRedirect();

    expect($document->refresh()->status)->toBe(CommercialDocumentStatus::Accepted)
        ->and($this->engagement->refresh()->outcome)->toBe(EngagementOutcome::Won)
        ->and($this->engagement->outcome_amount)->toBe(6960);

    $this->post(route('commercial-documents.decline', ['locale' => 'fr', 'token' => $document->token]))->assertStatus(409);
});

test('a draft or an expired quote cannot be opened or answered by the client', function () {
    $draft = CommercialDocument::factory()->create();
    $this->get(route('commercial-documents.show', ['locale' => 'fr', 'token' => $draft->token]))->assertNotFound();

    $expired = app(CommercialDocuments::class)->issue(CommercialDocument::factory()->create(['valid_until' => now()->subDay()]));
    $this->post(route('commercial-documents.accept', ['locale' => 'fr', 'token' => $expired->token]))->assertStatus(409);
});

test('an accepted quote becomes an invoice draft, which is then marked paid', function () {
    $documents = app(CommercialDocuments::class);
    $quote = $documents->issue($documents->draftFromEngagement($this->engagement));

    $this->actingAs($this->user)->post(route('admin.commercial-documents.convert', $quote), ['type' => 'invoice'])->assertRedirect();

    $invoice = CommercialDocument::query()->where('type', CommercialDocumentType::Invoice)->firstOrFail();
    expect($invoice->source_id)->toBe($quote->id)
        ->and($invoice->total)->toBe($quote->total)
        ->and($invoice->due_at)->not->toBeNull();

    $documents->issue($invoice);
    $this->actingAs($this->user)->patch(route('admin.commercial-documents.status', $invoice), ['status' => 'paid'])->assertRedirect();

    expect($invoice->refresh()->status)->toBe(CommercialDocumentStatus::Paid);
    $this->actingAs($this->user)->get(route('admin.commercial-documents.pdf', $invoice))->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

test('documents and billing settings are managed through the API', function () {
    Sanctum::actingAs($this->user);

    $id = $this->postJson("/api/v1/engagements/{$this->engagement->id}/documents")
        ->assertCreated()
        ->assertJsonPath('status', 'draft')
        ->assertJsonPath('total', 6960)
        ->json('id');

    $this->postJson("/api/v1/commercial-documents/{$id}/issue")->assertOk()->assertJsonPath('number', 'ADEV-DEV-'.now()->year.'-001');
    $this->putJson("/api/v1/commercial-documents/{$id}/status", ['status' => 'declined'])->assertOk()->assertJsonPath('status', 'declined');
    $this->deleteJson("/api/v1/commercial-documents/{$id}")->assertStatus(409);

    $this->putJson('/api/v1/billing-settings', [
        'billing_iban' => 'CH93 0076 2011 6238 5295 7',
        'billing_quote_validity_days' => 30,
        'billing_invoice_due_days' => 10,
        'billing_deposit_percent' => 50,
    ])->assertOk()->assertJsonPath('billing_deposit_percent', 50);
});

test('the MCP server drafts a quote from a request but never sends it', function () {
    $server = PortfolioServer::actingAs($this->user);

    $server->tool(DraftQuoteTool::class, [
        'engagement_id' => $this->engagement->id,
        'lines' => [['label' => 'Boutique complète', 'quantity' => 1, 'unit' => 'flat', 'unit_price' => 7500]],
    ])->assertOk()->assertSee('Brouillon prêt');

    $document = CommercialDocument::query()->firstOrFail();

    expect($document->isDraft())->toBeTrue()
        ->and($document->total)->toBe(7500);
    Mail::assertNothingSent();

    $server->tool(ListCommercialDocumentsTool::class, ['status' => 'draft'])->assertOk()->assertSee('Boutique en ligne');
});
