<?php

use App\Ai\Agents\WeeklyDigestWriter;
use App\Mail\WeeklyReportMail;
use App\Mcp\Servers\PortfolioServer;
use App\Mcp\Tools\GetWeeklyReportTool;
use App\Models\Engagement;
use App\Models\JobApplication;
use App\Models\JobApplicationEvent;
use App\Models\PageVisit;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\WeeklyReport;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Mail::fake();
    Profile::factory()->create(['email' => 'owner@example.test']);
    SiteSetting::factory()->create();
    $this->user = User::factory()->create();
});

/** Une semaine d'activité : 4 visites (2 la semaine d'avant), une estimation analysée, une candidature ouverte. */
function busyWeek(): JobApplication
{
    PageVisit::factory()->count(4)->create(['visitor_hash' => 'a', 'created_at' => now()->subDays(2)]);
    PageVisit::factory()->count(2)->create(['created_at' => now()->subDays(10)]);
    Engagement::factory()->create([
        'subject' => 'Site vitrine',
        'estimate' => ['billing' => 'fixed', 'currency' => 'CHF', 'hourly_rate' => 100, 'hours_min' => 36, 'hours_max' => 60, 'price_min' => 3600, 'price_max' => 6000, 'weeks_min' => 2, 'weeks_max' => 2, 'features' => []],
        'ai_analysis' => ['summary' => 'Vitrine.', 'hours_min' => 50, 'hours_max' => 70, 'billing' => 'hourly'],
    ]);

    $application = JobApplication::factory()->create(['company' => 'Globex', 'first_viewed_at' => now()->subDay()]);
    $application->events()->create(['type' => JobApplicationEvent::VIEW, 'created_at' => now()->subDay()]);
    $application->events()->create(['type' => JobApplicationEvent::CV_DOWNLOAD, 'created_at' => now()->subDay()]);

    return $application;
}

test('the report compares the week to the previous one and gathers estimates and applications', function () {
    busyWeek();

    $report = app(WeeklyReport::class)->build(withHighlights: false);

    expect($report['audience'])->toMatchArray(['visits' => 4, 'visitors' => 1, 'previous_visits' => 2, 'change' => 100])
        ->and($report['requests']['freelance'])->toBe(1)
        ->and($report['requests']['estimates'][0])->toMatchArray(['subject' => 'Site vitrine', 'ai_hours' => '50 – 70 h', 'ai_billing' => 'hourly'])
        ->and($report['applications']['viewed'][0])->toMatchArray(['company' => 'Globex', 'views' => 1, 'cv_downloads' => 1, 'first_time' => true])
        ->and($report['cv_downloads'])->toBe(1)
        ->and($report['todo'])->toHaveKey('engagements')
        ->and($report['todo'])->not->toHaveKey('comments');
});

test('the scheduled report is sent once a week to the profile email, with the AI highlights', function () {
    busyWeek();
    WeeklyDigestWriter::fake([json_encode(['highlights' => ['Globex a téléchargé ton CV.', '', 'Visites en hausse de 100 %.']])]);

    $this->artisan('report:weekly')->expectsOutput('Rapport envoyé.')->assertSuccessful();
    $this->artisan('report:weekly')->expectsOutput('Rapport déjà envoyé cette semaine.');

    Mail::assertSent(WeeklyReportMail::class, 1);
    Mail::assertSent(WeeklyReportMail::class, fn (WeeklyReportMail $mail) => $mail->hasTo('owner@example.test')
        && $mail->report['highlights'] === ['Globex a téléchargé ton CV.', 'Visites en hausse de 100 %.']
        && str_contains($mail->render(), 'Globex'));
});

test('nothing is sent when the report is off or the week is empty, unless forced', function () {
    $this->artisan('report:weekly')->expectsOutput('Semaine sans activité : rien envoyé.');

    SiteSetting::current()->update(['weekly_report_enabled' => false]);
    busyWeek();
    $this->artisan('report:weekly')->expectsOutput('Rapport désactivé dans l\'administration : rien envoyé.');
    Mail::assertNothingSent();

    WeeklyDigestWriter::fake()->preventStrayPrompts();
    WeeklyDigestWriter::fake([json_encode(['highlights' => ['Semaine calme.']])]);
    $this->artisan('report:weekly --force')->expectsOutput('Rapport envoyé.');
    Mail::assertSent(WeeklyReportMail::class, 1);
});

test('the admin page previews the mail, toggles it and sends it now', function () {
    busyWeek();
    WeeklyDigestWriter::fake([json_encode(['highlights' => ['Une semaine active.']])]);

    $this->actingAs($this->user)->get(route('admin.weekly-report.show'))
        ->assertInertia(fn ($page) => $page->component('admin/weekly-report/show')->where('enabled', true)->where('recipient', 'owner@example.test'));
    $this->actingAs($this->user)->get(route('admin.weekly-report.preview'))->assertOk()->assertSee('Globex');

    $this->actingAs($this->user)->put(route('admin.weekly-report.update'), ['weekly_report_enabled' => false])->assertRedirect();
    expect(SiteSetting::current()->weekly_report_enabled)->toBeFalse();

    $this->actingAs($this->user)->post(route('admin.weekly-report.send'))->assertRedirect();
    Mail::assertSent(WeeklyReportMail::class, 1);
});

test('the report is available through the API and MCP', function () {
    busyWeek();
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/weekly-report')->assertOk()
        ->assertJsonPath('enabled', true)
        ->assertJsonPath('report.audience.visits', 4)
        ->assertJsonPath('report.highlights', null);
    $this->putJson('/api/v1/weekly-report', ['enabled' => false])->assertOk()->assertJsonPath('enabled', false);

    PortfolioServer::actingAs($this->user)->tool(GetWeeklyReportTool::class)->assertOk()->assertSee('Globex');
});
