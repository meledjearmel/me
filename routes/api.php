<?php

use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AppointmentTypeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\CelebrationController;
use App\Http\Controllers\Api\V1\CongratulationController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\CvDownloadController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DomainController;
use App\Http\Controllers\Api\V1\EducationController;
use App\Http\Controllers\Api\V1\EngagementController;
use App\Http\Controllers\Api\V1\ExperienceController;
use App\Http\Controllers\Api\V1\JobProfileController;
use App\Http\Controllers\Api\V1\MusicGenreController;
use App\Http\Controllers\Api\V1\ProfessionalReferenceController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PushTokenController;
use App\Http\Controllers\Api\V1\SiteSettingController;
use App\Http\Controllers\Api\V1\SkillController;
use App\Http\Controllers\Api\V1\TechnologyCategoryController;
use App\Http\Controllers\Api\V1\TechnologyController;
use App\Http\Controllers\Api\V1\TechnologyIconController;
use App\Http\Controllers\Api\V1\TestimonialController;
use App\Http\Controllers\Api\V1\TrackController;
use App\Http\Controllers\Api\V1\TrashController;
use App\Models\JobProfile;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');
    Route::post('auth/two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])
        ->middleware('throttle:5,1')
        ->name('auth.two-factor-challenge');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::prefix('ai')->name('ai.')->middleware('throttle:ai-assist')->group(function (): void {
            Route::post('translate', [AiController::class, 'translate'])->name('translate');
            Route::post('improve', [AiController::class, 'improve'])->name('improve');
            Route::post('describe-technology', [AiController::class, 'describeTechnology'])->name('describe-technology');
        });

        Route::prefix('trash')->name('trash.')->group(function (): void {
            Route::get('/', [TrashController::class, 'index'])->name('index');
            Route::patch('{type}/{id}', [TrashController::class, 'restore'])->whereNumber('id')->name('restore');
            Route::delete('{type}/{id}', [TrashController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

        Route::apiResource('contacts', ContactController::class)->except('store');
        Route::apiResource('cv-downloads', CvDownloadController::class)->only(['index', 'show', 'destroy']);
        Route::apiResource('engagements', EngagementController::class)->except('store');
        Route::apiResource('appointments', AppointmentController::class)->only(['index', 'show', 'destroy']);
        Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
        Route::post('appointments/{appointment}/decline', [AppointmentController::class, 'decline'])->name('appointments.decline');
        Route::apiResource('appointment-types', AppointmentTypeController::class);
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
        Route::delete('projects/{project}/cover', [ProjectController::class, 'destroyCover'])->name('projects.cover.destroy');
        Route::delete('projects/{project}/gallery/{media}', [ProjectController::class, 'destroyGalleryImage'])->name('projects.gallery.destroy');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('profile/music', [ProfileController::class, 'destroyMusic'])->name('profile.music.destroy');

        Route::post('push-tokens', [PushTokenController::class, 'store'])->name('push-tokens.store');
        Route::delete('push-tokens', [PushTokenController::class, 'destroy'])->name('push-tokens.destroy');
    });
});
