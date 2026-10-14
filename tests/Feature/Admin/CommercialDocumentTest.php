<?php

use App\Ai\Agents\CommercialDocumentMailWriter;
use App\Enums\CommercialDocumentStatus;
use App\Enums\CommercialDocumentType;
use App\Enums\EngagementOutcome;
use App\Jobs\SendPushNotification;
use App\Mail\CommercialDocumentMail;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\DraftQuoteTool;
use App\Mcp\Tools\ListCommercialDocumentsTool;
use App\Models\CommercialDocument;
use App\Models\DocumentTemplate;
use App\Models\Engagement;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CommercialDocuments;
use Illuminate\Http\UploadedFile;
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

test('a template is previewed unsaved, then saved with its logo, and shapes the PDF of its type', function () {
    $this->actingAs($this->user)->get(route('admin.document-templates.edit', 'invoice'))->assertOk();

    $settings = [
        'layout' => 'modern',
        'primary_color' => '#112233',
        'accent_color' => '#ff8800',
        'font' => 'serif',
        'title' => ['fr' => 'Note d’honoraires', 'en' => ''],
        'intro' => ['fr' => 'Merci pour votre confiance.'],
        'footer' => ['fr' => 'Paiement à 30 jours.'],
        'show_logo' => '1',
        'show_bank_details' => '0',
        'show_signature' => '1',
        'logo' => UploadedFile::fake()->image('logo.png', 200, 60),
    ];

    $this->actingAs($this->user)
        ->post(route('admin.document-templates.preview', 'invoice'), $settings)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
    expect(DocumentTemplate::query()->where('type', 'invoice')->value('layout'))->toBe('classic');

    $this->actingAs($this->user)->post(route('admin.document-templates.update', 'invoice'), $settings)->assertRedirect();

    $template = DocumentTemplate::for(CommercialDocumentType::Invoice);
    expect($template->layout)->toBe('modern')
        ->and($template->getTranslation('title', 'fr'))->toBe('Note d’honoraires')
        ->and($template->getTranslations('title'))->not->toHaveKey('en')
        ->and($template->show_bank_details)->toBeFalse()
        ->and($template->logoDataUri())->toStartWith('data:image/png;base64,');

    $this->actingAs($this->user)
        ->post(route('admin.document-templates.preview', 'invoice'), [...$settings, 'logo' => null, 'primary_color' => 'rouge'])
        ->assertSessionHasErrors('primary_color');
});

test('a template created on first use carries the default design', function () {
    $this->actingAs($this->user)->get(route('admin.document-templates.edit', 'proforma'))
        ->assertInertia(fn ($page) => $page
            ->where('template.layout', 'classic')
            ->where('template.primary_color', '#0f2747')
            ->where('template.font', 'sans')
            ->where('template.show_bank_details', true));
});

test('templates are read, saved and previewed through the API', function () {
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/document-templates/quote')->assertOk()->assertJsonPath('show_signature', true);
    $this->postJson('/api/v1/document-templates/quote', ['layout' => 'minimal', 'primary_color' => '#000000', 'accent_color' => '#cccccc', 'font' => 'mono'])
        ->assertOk()
        ->assertJsonPath('layout', 'minimal');
    $this->postJson('/api/v1/document-templates/quote/preview', ['layout' => 'classic', 'primary_color' => '#000000', 'accent_color' => '#cccccc', 'font' => 'sans', 'preview_locale' => 'en'])
        ->assertOk();
    $this->getJson('/api/v1/document-templates/receipt')->assertNotFound();
});

test('the dashboard follows what is invoiced, collected, outstanding and overdue', function () {
    $documents = app(CommercialDocuments::class);
    $paid = $documents->issue(CommercialDocument::factory()->invoice()->create());
    $documents->markPaid($paid);
    $late = $documents->issue(CommercialDocument::factory()->invoice()->create(['due_at' => now()->subDays(5)]));
    $documents->issue(CommercialDocument::factory()->invoice()->create());
    $documents->issue(CommercialDocument::factory()->invoice()->create(['type' => CommercialDocumentType::Proforma]));
    $documents->accept($documents->issue(CommercialDocument::factory()->create()));
    $documents->issue(CommercialDocument::factory()->create());

    // Chaque facture vaut 40 h × 100 + 1 500 = 5 500 CHF.
    $this->actingAs($this->user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('billing.by_currency', [['currency' => 'CHF', 'invoiced' => 16500, 'collected' => 5500, 'outstanding' => 11000, 'overdue' => 5500]])
        ->where('billing.quotes', ['issued' => 2, 'accepted' => 1, 'declined' => 0, 'acceptance_rate' => 100])
        ->where('billing.overdue_invoices.0.id', $late->id)
        ->where('billing.overdue_invoices.0.days_late', 5)
        ->where('todo.overdue_invoices', 1)
    );
});

test('quotes without answer and overdue invoices are flagged each day, up to the maximum', function () {
    SiteSetting::current()->update(['billing_quote_reminder_days' => 7, 'billing_invoice_reminder_days' => 3, 'billing_max_reminders' => 2]);
    $documents = app(CommercialDocuments::class);
    $quote = $documents->issue(CommercialDocument::factory()->create());
    $quote->forceFill(['sent_at' => now()->subDays(8)])->save();
    $recentQuote = $documents->issue(CommercialDocument::factory()->create());
    $recentQuote->forceFill(['sent_at' => now()->subDays(2)])->save();
    $lateInvoice = $documents->issue(CommercialDocument::factory()->invoice()->create(['due_at' => now()->subDays(4)]));
    $documents->issue(CommercialDocument::factory()->invoice()->create(['due_at' => now()->subDay()]));

    $this->artisan('documents:flag-reminders')->assertSuccessful();

    expect($quote->refresh()->reminder_due_at)->not->toBeNull()
        ->and($lateInvoice->refresh()->reminder_due_at)->not->toBeNull()
        ->and(CommercialDocument::query()->whereNotNull('reminder_due_at')->count())->toBe(2);
    Queue::assertPushed(SendPushNotification::class);

    // Relancé : la suggestion disparaît, et revient après le délai, jusqu'au maximum.
    $this->actingAs($this->user)
        ->post(route('admin.commercial-documents.remind', $quote), ['subject' => 'Relance', 'message' => 'Avez-vous pu regarder ?'])
        ->assertRedirect();
    expect($quote->refresh()->reminders_count)->toBe(1)->and($quote->reminder_due_at)->toBeNull();
    Mail::assertSent(CommercialDocumentMail::class);

    $this->travel(8)->days();
    $documents->flagReminders();
    expect($quote->refresh()->reminder_due_at)->not->toBeNull();

    $documents->remind($quote, 'Relance', 'Dernière relance.');
    $this->travel(8)->days();
    $documents->flagReminders();
    expect($quote->refresh()->reminder_due_at)->toBeNull();

    // Écartée, la relance de la facture ne part pas ; une facture payée n'est plus suivie.
    $this->actingAs($this->user)->delete(route('admin.commercial-documents.dismiss-reminder', $lateInvoice))->assertRedirect();
    expect($lateInvoice->refresh()->reminder_due_at)->toBeNull()->and($lateInvoice->reminders_count)->toBe(0);
    $documents->markPaid($lateInvoice);
    $this->travel(10)->days();
    $documents->flagReminders();
    expect($lateInvoice->refresh()->reminder_due_at)->toBeNull();
});

test('the AI writes a reminder rather than a first email when asked', function () {
    CommercialDocumentMailWriter::fake([json_encode(['subject' => 'Relance : votre devis', 'message' => 'Je reviens vers vous.'])]);
    $documents = app(CommercialDocuments::class);
    $quote = $documents->issue($documents->draftFromEngagement($this->engagement));
    $quote->forceFill(['sent_at' => now()->subDays(8)])->save();

    $this->actingAs($this->user)
        ->postJson(route('admin.commercial-documents.suggest-mail', ['commercial_document' => $quote, 'reminder' => 1]))
        ->assertOk()
        ->assertJsonPath('subject', 'Relance : votre devis');

    CommercialDocumentMailWriter::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'relance n° 1'));
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
