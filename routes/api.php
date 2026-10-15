<?php

use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AppointmentTypeController;
use App\Http\Controllers\Api\V1\AppReleaseController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BillingSettingsController;
use App\Http\Controllers\Api\V1\CelebrationController;
use App\Http\Controllers\Api\V1\CertificationController;
use App\Http\Controllers\Api\V1\CommercialDocumentController;
use App\Http\Controllers\Api\V1\CongratulationController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\ContentRevisionController;
use App\Http\Controllers\Api\V1\ContentStatusController;
use App\Http\Controllers\Api\V1\CvDownloadController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentTemplateController;
use App\Http\Controllers\Api\V1\DomainController;
use App\Http\Controllers\Api\V1\EducationController;
use App\Http\Controllers\Api\V1\EngagementController;
use App\Http\Controllers\Api\V1\EstimatorConfigurationController;
use App\Http\Controllers\Api\V1\EstimatorItemController;
use App\Http\Controllers\Api\V1\EstimatorRateController;
use App\Http\Controllers\Api\V1\EstimatorSettingsController;
use App\Http\Controllers\Api\V1\ExperienceController;
use App\Http\Controllers\Api\V1\GitHubController;
use App\Http\Controllers\Api\V1\JobApplicationController;
use App\Http\Controllers\Api\V1\JobApplicationCvController;
use App\Http\Controllers\Api\V1\JobProfileController;
use App\Http\Controllers\Api\V1\McpActivityController;
use App\Http\Controllers\Api\V1\McpCommandController;
use App\Http\Controllers\Api\V1\MusicGenreController;
use App\Http\Controllers\Api\V1\PostCommentController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\PostSeriesController;
use App\Http\Controllers\Api\V1\PostSyndicationController;
use App\Http\Controllers\Api\V1\PostTagController;
use App\Http\Controllers\Api\V1\ProfessionalReferenceController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PushTokenController;
use App\Http\Controllers\Api\V1\ReviewInvitationController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SiteSettingController;
use App\Http\Controllers\Api\V1\SkillController;
use App\Http\Controllers\Api\V1\SubscriberController;
use App\Http\Controllers\Api\V1\TechnologyCategoryController;
use App\Http\Controllers\Api\V1\TechnologyController;
use App\Http\Controllers\Api\V1\TechnologyIconController;
use App\Http\Controllers\Api\V1\TestimonialController;
use App\Http\Controllers\Api\V1\TrackController;
use App\Http\Controllers\Api\V1\TrashController;
use App\Http\Controllers\Api\V1\UsesItemController;
use App\Http\Controllers\Api\V1\WeeklyReportController;
use App\Http\Middleware\RejectMcpTokens;
use App\Models\JobProfile;
use App\Services\ContentStatus;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');
    Route::post('auth/two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])
        ->middleware('throttle:5,1')
        ->name('auth.two-factor-challenge');

    Route::put('app-releases/{app_release}/file', [AppReleaseController::class, 'upload'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('app-releases.upload');
    Route::get('app-releases/{app_release}/download', [AppReleaseController::class, 'download'])
        ->middleware(['signed', 'throttle:20,1'])
        ->name('app-releases.download');

    Route::middleware(['auth:sanctum', RejectMcpTokens::class])->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('weekly-report', [WeeklyReportController::class, 'show'])->name('weekly-report.show');
        Route::put('weekly-report', [WeeklyReportController::class, 'update'])->name('weekly-report.update');
        Route::post('weekly-report/send', [WeeklyReportController::class, 'send'])->middleware('throttle:5,1')->name('weekly-report.send');

        Route::prefix('ai')->name('ai.')->middleware('throttle:ai-assist')->group(function (): void {
            Route::post('translate', [AiController::class, 'translate'])->name('translate');
            Route::post('improve', [AiController::class, 'improve'])->name('improve');
            Route::post('describe-technology', [AiController::class, 'describeTechnology'])->name('describe-technology');
            Route::post('translate-html', [AiController::class, 'translateHtml'])->name('translate-html');
            Route::post('write-post', [AiController::class, 'writePost'])->name('write-post');
            Route::post('draft-project', [AiController::class, 'draftProject'])->name('draft-project');
            Route::post('suggest-estimator-item', [AiController::class, 'suggestEstimatorItem'])->name('suggest-estimator-item');
            Route::post('propose-estimator-grid', [AiController::class, 'proposeEstimatorGrid'])->name('propose-estimator-grid');
            Route::post('draft-service', [AiController::class, 'draftService'])->name('draft-service');
            Route::post('draft-job-application', [AiController::class, 'draftJobApplication'])->name('draft-job-application');
            Route::post('engagements/{engagement}/analyze', [EngagementController::class, 'analyze'])->name('engagements.analyze');
        });

        Route::prefix('trash')->name('trash.')->group(function (): void {
            Route::get('/', [TrashController::class, 'index'])->name('index');
            Route::patch('{type}/{id}', [TrashController::class, 'restore'])->whereNumber('id')->name('restore');
            Route::delete('{type}/{id}', [TrashController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        Route::apiResource('contacts', ContactController::class)->except('store');
        Route::apiResource('cv-downloads', CvDownloadController::class)->only(['index', 'show', 'destroy']);
        Route::apiResource('engagements', EngagementController::class)->except('store');
        Route::put('engagements/{engagement}/outcome', [EngagementController::class, 'outcome'])->name('engagements.outcome');
        Route::post('engagements/{engagement}/documents', [CommercialDocumentController::class, 'fromEngagement'])->name('engagements.documents.store');
        Route::apiResource('commercial-documents', CommercialDocumentController::class);
        Route::prefix('commercial-documents/{commercial_document}')->name('commercial-documents.')->group(function () {
            Route::get('pdf', [CommercialDocumentController::class, 'pdf'])->name('pdf');
            Route::post('issue', [CommercialDocumentController::class, 'issue'])->name('issue');
            Route::post('suggest-mail', [CommercialDocumentController::class, 'suggestMail'])->name('suggest-mail');
            Route::post('send', [CommercialDocumentController::class, 'send'])->name('send');
            Route::post('remind', [CommercialDocumentController::class, 'remind'])->name('remind');
            Route::delete('reminder', [CommercialDocumentController::class, 'dismissReminder'])->name('dismiss-reminder');
            Route::post('convert', [CommercialDocumentController::class, 'convert'])->name('convert');
            Route::put('status', [CommercialDocumentController::class, 'status'])->name('status');
        });
        Route::get('document-templates/{type}', [DocumentTemplateController::class, 'show'])->name('document-templates.show');
        Route::post('document-templates/{type}', [DocumentTemplateController::class, 'update'])->name('document-templates.update');
        Route::post('document-templates/{type}/preview', [DocumentTemplateController::class, 'preview'])->name('document-templates.preview');
        Route::get('billing-settings', [BillingSettingsController::class, 'show'])->name('billing-settings.show');
        Route::put('billing-settings', [BillingSettingsController::class, 'update'])->name('billing-settings.update');
        Route::apiResource('appointments', AppointmentController::class)->only(['index', 'show', 'destroy']);
        Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
        Route::post('appointments/{appointment}/decline', [AppointmentController::class, 'decline'])->name('appointments.decline');
        Route::apiResource('appointment-types', AppointmentTypeController::class);
        Route::apiResource('estimator-items', EstimatorItemController::class);
        Route::apiResource('estimator-rates', EstimatorRateController::class);
        Route::get('estimator-settings', [EstimatorSettingsController::class, 'show'])->name('estimator-settings.show');
        Route::put('estimator-settings', [EstimatorSettingsController::class, 'update'])->name('estimator-settings.update');
        Route::post('estimator-configuration', [EstimatorConfigurationController::class, 'store'])->name('estimator-configuration.store');
        Route::get('site-settings', [SiteSettingController::class, 'show'])->name('site-settings.show');
        Route::patch('site-settings', [SiteSettingController::class, 'update'])->name('site-settings.update');
        Route::prefix('availability')->name('availability.')->group(function (): void {
            Route::get('/', [AvailabilityController::class, 'index'])->name('index');
            Route::post('rules', [AvailabilityController::class, 'storeRule'])->name('rules.store');
            Route::post('blocked-periods', [AvailabilityController::class, 'storeBlockedPeriod'])->name('blocked-periods.store');
            Route::delete('{schedule}', [AvailabilityController::class, 'destroy'])->name('destroy');
        });
        Route::apiResource('testimonials', TestimonialController::class)->except('store');
        Route::delete('testimonials/{testimonial}/video', [TestimonialController::class, 'destroyVideo'])->name('testimonials.video.destroy');
        Route::apiResource('congratulations', CongratulationController::class)->only(['index', 'show']);

        Route::get('mcp-activity', [McpActivityController::class, 'index'])->name('mcp-activity.index');
        Route::get('mcp-commands', [McpCommandController::class, 'index'])->name('mcp-commands.index');
        Route::patch('status/{type}/{id}', ContentStatusController::class)->whereIn('type', ContentStatus::types())->whereNumber('id')->name('content-status.update');

        Route::prefix('revisions')->name('revisions.')->group(function (): void {
            Route::get('/', [ContentRevisionController::class, 'index'])->name('index');
            Route::get('{revision}', [ContentRevisionController::class, 'show'])->name('show');
            Route::post('{revision}/apply', [ContentRevisionController::class, 'apply'])->name('apply');
            Route::post('{revision}/reject', [ContentRevisionController::class, 'reject'])->name('reject');
        });

        Route::apiResource('domains', DomainController::class);
        Route::apiResource('music-genres', MusicGenreController::class);
        Route::apiResource('tracks', TrackController::class);
        Route::prefix('technology-icons')->name('technology-icons.')->group(function (): void {
            Route::get('/', [TechnologyIconController::class, 'index'])->name('index');

            // Recherche et import appellent le catalogue Iconify : on les borne.
            Route::middleware('throttle:technology-icons')->group(function (): void {
                Route::get('search', [TechnologyIconController::class, 'search'])->name('search');
                Route::post('/', [TechnologyIconController::class, 'store'])->name('store');
                Route::post('upload', [TechnologyIconController::class, 'upload'])->name('upload');
            });
        });
        Route::apiResource('technology-categories', TechnologyCategoryController::class);
        Route::apiResource('technologies', TechnologyController::class);
        Route::apiResource('job-profiles', JobProfileController::class);
        Route::delete('job-profiles/{job_profile}/cv/{locale}', [JobProfileController::class, 'destroyCv'])
            ->whereIn('locale', JobProfile::CV_LOCALES)
            ->name('job-profiles.cv.destroy');
        Route::apiResource('skills', SkillController::class);
        Route::apiResource('educations', EducationController::class);
        Route::apiResource('professional-references', ProfessionalReferenceController::class);
        Route::apiResource('celebrations', CelebrationController::class);
        Route::apiResource('experiences', ExperienceController::class);
        Route::apiResource('projects', ProjectController::class);
        Route::get('posts/tags', [PostController::class, 'tags'])->name('posts.tags');
        Route::get('posts/mentions', [PostController::class, 'mentions'])->name('posts.mentions');
        Route::post('posts/images', [PostController::class, 'storeImage'])->name('posts.images.store');
        Route::apiResource('posts', PostController::class);
        Route::apiResource('post-comments', PostCommentController::class)->except('store')->parameters(['post-comments' => 'comment']);
        Route::apiResource('post-tags', PostTagController::class)->except('store');
        Route::apiResource('post-series', PostSeriesController::class)
            ->except('store')
            ->parameters(['post-series' => 'postSeries']);
        Route::apiResource('subscribers', SubscriberController::class)->only(['index', 'destroy']);
        Route::apiResource('review-invitations', ReviewInvitationController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('uses-items', UsesItemController::class);
        Route::apiResource('services', ServiceController::class);
        Route::get('job-applications/{job_application}/events', [JobApplicationController::class, 'events'])->name('job-applications.events');
        Route::get('job-applications/{job_application}/cv', [JobApplicationCvController::class, 'show'])->name('job-applications.cv.show');
        Route::post('job-applications/{job_application}/cv/tailor', [JobApplicationCvController::class, 'tailor'])->middleware('throttle:ai-assist')->name('job-applications.cv.tailor');
        Route::put('job-applications/{job_application}/cv', [JobApplicationCvController::class, 'update'])->name('job-applications.cv.update');
        Route::delete('job-applications/{job_application}/cv', [JobApplicationCvController::class, 'destroy'])->name('job-applications.cv.destroy');
        Route::apiResource('job-applications', JobApplicationController::class);
        Route::apiResource('certifications', CertificationController::class);
        Route::get('github', [GitHubController::class, 'show'])->name('github.show');
        Route::put('github', [GitHubController::class, 'update'])->name('github.update');
        Route::post('github/sync', [GitHubController::class, 'sync'])->middleware('throttle:6,1')->name('github.sync');
        Route::delete('certifications/{certification}/badge', [CertificationController::class, 'destroyBadge'])->name('certifications.badge.destroy');
        Route::delete('posts/{post}/cover', [PostController::class, 'destroyCover'])->name('posts.cover.destroy');
        Route::get('syndication', [PostSyndicationController::class, 'settings'])->name('syndication.show');
        Route::put('syndication', [PostSyndicationController::class, 'updateSettings'])->name('syndication.update');
        Route::get('syndication/hashnode-publications', [PostSyndicationController::class, 'hashnodePublications'])->name('syndication.hashnode-publications');
        Route::get('posts/{post}/syndications', [PostSyndicationController::class, 'show'])->name('posts.syndications.show');
        Route::post('posts/{post}/syndications/{platform}', [PostSyndicationController::class, 'store'])
            ->whereIn('platform', ['devto', 'hashnode'])->middleware('throttle:10,1')->name('posts.syndications.store');
        Route::post('posts/{post}/social-texts/{locale}', [PostSyndicationController::class, 'generateSocialTexts'])
            ->whereIn('locale', ['fr', 'en'])->middleware('throttle:ai-assist')->name('posts.social-texts.generate');
        Route::put('posts/{post}/social-texts', [PostSyndicationController::class, 'updateSocialTexts'])->name('posts.social-texts.update');
        Route::delete('projects/{project}/cover', [ProjectController::class, 'destroyCover'])->name('projects.cover.destroy');
        Route::delete('projects/{project}/gallery/{media}', [ProjectController::class, 'destroyGalleryImage'])->name('projects.gallery.destroy');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('profile/music', [ProfileController::class, 'destroyMusic'])->name('profile.music.destroy');

        Route::get('app/latest', [AppReleaseController::class, 'latest'])->name('app.latest');

        Route::post('push-tokens', [PushTokenController::class, 'store'])->name('push-tokens.store');
        Route::delete('push-tokens', [PushTokenController::class, 'destroy'])->name('push-tokens.destroy');
    });
});
