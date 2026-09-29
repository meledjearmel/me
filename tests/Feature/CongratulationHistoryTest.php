<?php

use App\Enums\CongratulationSource;
use App\Jobs\SendPushNotification;
use App\Models\Celebration;
use App\Models\Congratulation;
use App\Models\Counter;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Queue::fake();
    Celebration::query()->delete();
});

test('congratulating from the about page records why and notifies', function () {
    $this->postJson('/fr/congratulations', ['count' => 3])->assertOk();

    $congratulation = Congratulation::query()->sole();

    expect($congratulation->source)->toBe(CongratulationSource::About)
        ->and($congratulation->reason)->toBe(Congratulation::ABOUT_REASON)
        ->and($congratulation->count)->toBe(3)
        ->and($congratulation->locale)->toBe('fr');

    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->body === 'Vous avez reçu 3 félicitations pour votre distinction de meilleur agent du CIAPOL'
        && $job->data === ['type' => 'congratulation', 'id' => (string) $congratulation->id]);
});

test('congratulating a surprise records its message as the reason', function () {
    $celebration = Celebration::factory()->create([
        'message' => ['fr' => 'Nouveau diplôme !', 'en' => 'New degree!'],
        'congratulated_for' => 'votre prix de meilleur agent',
    ]);

    $this->postJson("/en/celebrations/{$celebration->id}/congratulations", ['count' => 2])->assertOk();

    $congratulation = Congratulation::query()->sole();

    expect($congratulation->source)->toBe(CongratulationSource::Surprise)
        ->and($congratulation->celebration_id)->toBe($celebration->id)
        ->and($congratulation->reason)->toBe('Nouveau diplôme !')
        ->and($congratulation->locale)->toBe('en');

    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->body === 'Vous avez reçu 2 félicitations pour votre prix de meilleur agent');
});

test('a single congratulation is written in the singular', function () {
    expect(Congratulation::notificationBody(1))->toBe('Vous avez reçu 1 félicitation pour votre distinction de meilleur agent du CIAPOL');
});

test('notifications are throttled per reason', function () {
    $celebration = Celebration::factory()->create();

    $this->postJson('/fr/congratulations', ['count' => 1]);
    $this->postJson('/fr/congratulations', ['count' => 1]);
    $this->postJson("/fr/celebrations/{$celebration->id}/congratulations", ['count' => 1]);

    expect(Congratulation::query()->count())->toBe(3);
    Queue::assertPushed(SendPushNotification::class, 2);

    $this->travel(Congratulation::NOTIFY_EVERY_MINUTES + 1)->minutes();
    $this->postJson('/fr/congratulations', ['count' => 1]);

    Queue::assertPushed(SendPushNotification::class, 3);
});

test('the reason survives the deletion of its surprise', function () {
    $celebration = Celebration::factory()->create(['message' => ['fr' => 'Promu !', 'en' => 'Promoted!']]);
    $this->postJson("/fr/celebrations/{$celebration->id}/congratulations", ['count' => 1]);

    $celebration->delete();

    expect(Congratulation::query()->sole())
        ->celebration_id->toBeNull()
        ->reason->toBe('Promu !');
});

test('congratulations received before the history are backfilled for the distinction', function () {
    Counter::add(Counter::CONGRATULATIONS, 70000);
    $migration = require database_path('migrations/2026_09_29_035659_backfill_legacy_congratulations.php');

    $migration->up();

    expect(Congratulation::query()->sum('count'))->toBe(70000)
        ->and(Congratulation::query()->pluck('count')->all())->toBe([65535, 4465])
        ->and(Congratulation::query()->first())
        ->source->toBe(CongratulationSource::About)
        ->reason->toBe(Congratulation::ABOUT_REASON)
        ->locale->toBeNull();
    expect(Counter::total(Counter::CONGRATULATIONS))->toBe(70000);
    Queue::assertNothingPushed();

    $migration->down();

    expect(Congratulation::query()->count())->toBe(0);
});

test('the admin lists congratulations filtered by source', function () {
    Congratulation::factory()->create();
    Congratulation::factory()->create(['source' => CongratulationSource::Surprise, 'reason' => 'Promu !']);

    $this->get(route('admin.congratulations.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.congratulations.index', ['source' => 'surprise']))
        ->assertInertia(fn ($page) => $page
            ->component('admin/congratulations/index')
            ->has('congratulations.data', 1)
            ->where('congratulations.data.0.reason', 'Promu !')
        );
});

test('the api lists and shows congratulations', function () {
    Sanctum::actingAs(User::factory()->create());
    $congratulation = Congratulation::factory()->create(['count' => 4]);

    $this->getJson(route('api.v1.congratulations.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $congratulation->id)
        ->assertJsonPath('data.0.source', 'about');

    $this->getJson(route('api.v1.congratulations.show', $congratulation))
        ->assertOk()
        ->assertJsonPath('reason', Congratulation::ABOUT_REASON)
        ->assertJsonPath('count', 4);
});
