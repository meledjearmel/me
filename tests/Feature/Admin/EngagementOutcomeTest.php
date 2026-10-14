<?php

use App\Enums\AppointmentStatus;
use App\Enums\EngagementOutcome;
use App\Enums\EngagementStatus;
use App\Models\Appointment;
use App\Models\Engagement;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->engagement = Engagement::factory()->create([
        'estimate' => ['currency' => 'CHF', 'price_min' => 4000, 'price_max' => 6000, 'hours_min' => 40, 'hours_max' => 60, 'weeks_min' => 2, 'weeks_max' => 3, 'features' => []],
    ]);
});

test('a request is marked as won with its signed amount, which also handles it', function () {
    $this->actingAs($this->user)
        ->put(route('admin.engagements.outcome', $this->engagement), ['outcome' => 'won', 'outcome_amount' => 5500, 'outcome_currency' => 'CHF'])
        ->assertRedirect();

    $this->engagement->refresh();

    expect($this->engagement->outcome)->toBe(EngagementOutcome::Won)
        ->and($this->engagement->outcome_amount)->toBe(5500)
        ->and($this->engagement->outcome_at)->not->toBeNull()
        ->and($this->engagement->status)->toBe(EngagementStatus::Handled);
});

test('a won request needs its amount and currency, and a lost one drops them', function () {
    $this->actingAs($this->user)
        ->put(route('admin.engagements.outcome', $this->engagement), ['outcome' => 'won'])
        ->assertSessionHasErrors(['outcome_amount', 'outcome_currency']);

    $this->engagement->recordOutcome(EngagementOutcome::Won, 5000, 'CHF');
    $this->actingAs($this->user)->put(route('admin.engagements.outcome', $this->engagement), ['outcome' => 'lost', 'outcome_amount' => 9000]);

    expect($this->engagement->refresh()->outcome)->toBe(EngagementOutcome::Lost)
        ->and($this->engagement->outcome_amount)->toBeNull();

    $this->actingAs($this->user)->put(route('admin.engagements.outcome', $this->engagement), ['outcome' => '']);

    expect($this->engagement->refresh()->outcome)->toBeNull()
        ->and($this->engagement->outcome_at)->toBeNull();
});

test('the outcome is recorded through the API', function () {
    Sanctum::actingAs($this->user);

    $this->putJson("/api/v1/engagements/{$this->engagement->id}/outcome", ['outcome' => 'won', 'outcome_amount' => 6000, 'outcome_currency' => 'CHF'])
        ->assertOk()
        ->assertJsonPath('outcome', 'won')
        ->assertJsonPath('outcome_amount', 6000);
});

test('the dashboard reports the signed value and how it compares with the estimate', function () {
    // Signée 6 000 pour une fourchette 4 000 – 6 000 (milieu 5 000) : +20 %.
    $this->engagement->recordOutcome(EngagementOutcome::Won, 6000, 'CHF');
    Engagement::factory()->create()->recordOutcome(EngagementOutcome::Lost);
    Engagement::factory()->create()->recordOutcome(EngagementOutcome::Won, 3000, 'EUR');
    Engagement::factory()->create()->recordOutcome(EngagementOutcome::Won, 9000, 'CHF');
    Engagement::query()->latest('id')->first()->forceFill(['outcome_at' => now()->subDays(90)])->save();

    $this->actingAs($this->user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('outcomes.won', 2)
        ->where('outcomes.lost', 1)
        ->where('outcomes.win_rate', 66.7)
        ->where('outcomes.by_currency', [
            ['currency' => 'CHF', 'count' => 1, 'amount' => 6000, 'average' => 6000],
            ['currency' => 'EUR', 'count' => 1, 'amount' => 3000, 'average' => 3000],
        ])
        ->where('outcomes.estimate_gap', ['compared' => 1, 'gap_percent' => 20])
    );
});

test('the dashboard reports appointment requests, their confirmation rate and response time', function () {
    Appointment::factory()->create(['status' => AppointmentStatus::Confirmed, 'confirmed_at' => now()->addHours(4)]);
    Appointment::factory()->create(['status' => AppointmentStatus::Declined]);
    Appointment::factory()->create(['status' => AppointmentStatus::Pending]);

    $this->actingAs($this->user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('appointments.total', 3)
        ->where('appointments.by_status', ['pending' => 1, 'confirmed' => 1, 'declined' => 1, 'cancelled' => 0])
        ->where('appointments.confirmation_rate', 50)
        ->where('appointments.response_hours', 4)
        ->where('appointments.by_location', [['label' => 'video', 'count' => 3]])
    );
});
