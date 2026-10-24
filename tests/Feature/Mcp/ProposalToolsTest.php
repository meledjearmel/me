<?php

use App\Enums\ProposalStatus;
use App\Mcp\Prompts\WriteProposalPrompt;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\ListProposalsTool;
use App\Mcp\Tools\SaveProposalTool;
use App\Models\Proposal;
use App\Models\User;

beforeEach(function () {
    $this->server = PortfolioServer::actingAs(User::factory()->create());
});

test('save-proposal stores a private draft, then records the final text and the outcome', function () {
    $this->server->tool(SaveProposalTool::class, [
        'platform' => 'upwork',
        'locale' => 'en',
        'job_title' => 'Laravel API for a booking app',
        'client_name' => 'John',
        'body' => 'Your booking flow needs an API that never double-books.',
        'screening_answers' => 'Q: Experience with Stripe? A: Yes, three projects.',
    ])->assertOk()->assertSee('brouillon');

    $proposal = Proposal::query()->sole();

    expect($proposal->status)->toBe(ProposalStatus::Draft)
        ->and($proposal->sent_at)->toBeNull()
        ->and($proposal->drafted_by)->not->toBeNull();

    $this->server->tool(SaveProposalTool::class, [
        'id' => $proposal->id,
        'body' => 'Final text as sent.',
        'status' => 'interview',
    ])->assertOk();

    expect($proposal->refresh()->body)->toBe('Final text as sent.')
        ->and($proposal->status)->toBe(ProposalStatus::Interview)
        ->and($proposal->sent_at)->not->toBeNull()
        ->and($proposal->job_title)->toBe('Laravel API for a booking app');
});

test('save-proposal needs the essentials to create one', function () {
    $this->server->tool(SaveProposalTool::class, ['platform' => 'upwork'])->assertHasErrors();

    expect(Proposal::query()->exists())->toBeFalse();
});

test('list-proposals puts the proposals that worked first, as examples', function () {
    Proposal::factory()->status(ProposalStatus::Declined)->create(['body' => 'Declined one']);
    Proposal::factory()->status(ProposalStatus::Hired)->create(['body' => 'Hired one']);
    Proposal::factory()->create(['platform' => 'malt', 'body' => 'Malt one']);

    $this->server->tool(ListProposalsTool::class, ['platform' => 'upwork'])
        ->assertOk()
        ->assertSee(['Hired one', 'Declined one'])
        ->assertDontSee('Malt one');

    expect(Proposal::query()->bestFirst()->pluck('body')->first())->toBe('Hired one');
});

test('write-proposal carries the writing rules, the platform and the posting', function () {
    $this->server->prompt(WriteProposalPrompt::class, [
        'job_posting' => 'We need a Flutter developer for a delivery app.',
        'platform' => 'malt',
        'locale' => 'en',
    ])->assertOk()->assertSee(['list-proposals', 'save-proposal', 'malt', 'in English', 'We need a Flutter developer', 'land an interview', 'Never save anything before I explicitly validate']);
});
