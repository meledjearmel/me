<?php

use App\Http\Controllers\Admin\AiAssistController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\AppointmentTypeController;
use App\Http\Controllers\Admin\AvailabilityController;
use App\Http\Controllers\Admin\CelebrationController;
use App\Http\Controllers\Admin\CongratulationController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\CvDownloadController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\EngagementController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\JobProfileController;
use App\Http\Controllers\Admin\MusicGenreController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PostTagController;
use App\Http\Controllers\Admin\ProfessionalReferenceController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\TechnologyCategoryController;
use App\Http\Controllers\Admin\TechnologyController;
use App\Http\Controllers\Admin\TechnologyIconController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\TrackController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\UsesItemController;
use App\Models\JobProfile;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile/music', [ProfileController::class, 'destroyMusic'])->name('profile.music.destroy');
    Route::get('site-settings', [SiteSettingController::class, 'edit'])->name('site-settings.edit');
    Route::patch('site-settings', [SiteSettingController::class, 'update'])->name('site-settings.update');

    Route::prefix('ai')->name('ai.')->middleware('throttle:ai-assist')->group(function () {
        Route::post('translate', [AiAssistController::class, 'translate'])->name('translate');
        Route::post('improve', [AiAssistController::class, 'improve'])->name('improve');
        Route::post('translate-html', [AiAssistController::class, 'translateHtml'])->name('translate-html');
        Route::post('write-post', [AiAssistController::class, 'writePost'])->name('write-post');
        Route::post('describe-technology', [AiAssistController::class, 'describeTechnology'])->name('describe-technology');
    });

    Route::prefix('trash')->name('trash.')->group(function () {
        Route::get('/', [TrashController::class, 'index'])->name('index');
        Route::patch('{type}/{id}', [TrashController::class, 'restore'])->whereNumber('id')->name('restore');
        Route::delete('{type}/{id}', [TrashController::class, 'forceDelete'])->whereNumber('id')->name('force-delete');
    });

    Route::resource('domains', DomainController::class);
    Route::resource('music-genres', MusicGenreController::class)->except('show');
    Route::resource('uses-items', UsesItemController::class)->except('show');
    Route::resource('tracks', TrackController::class)->except('show');
    Route::prefix('technology-icons')->name('technology-icons.')->group(function () {
        Route::get('search', [TechnologyIconController::class, 'search'])->name('search');
        Route::post('/', [TechnologyIconController::class, 'store'])->name('store');
        Route::post('upload', [TechnologyIconController::class, 'upload'])->name('upload');
    });
    Route::resource('technology-categories', TechnologyCategoryController::class);
    Route::resource('celebrations', CelebrationController::class);
    Route::get('congratulations', [CongratulationController::class, 'index'])->name('congratulations.index');
    Route::resource('technologies', TechnologyController::class);
    Route::resource('job-profiles', JobProfileController::class);
    Route::delete('job-profiles/{job_profile}/cv/{locale}', [JobProfileController::class, 'destroyCv'])
        ->whereIn('locale', JobProfile::CV_LOCALES)
        ->name('job-profiles.cv.destroy');
    Route::resource('skills', SkillController::class);
    Route::resource('educations', EducationController::class);
    Route::resource('experiences', ExperienceController::class);
    Route::resource('projects', ProjectController::class);
    Route::post('posts/images', [PostController::class, 'storeImage'])->name('posts.images.store');
    Route::resource('posts', PostController::class)->except('show');
    Route::resource('post-tags', PostTagController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::delete('posts/{post}/cover', [PostController::class, 'destroyCover'])->name('posts.cover.destroy');
    Route::delete('projects/{project}/cover', [ProjectController::class, 'destroyCover'])->name('projects.cover.destroy');
    Route::delete('projects/{project}/gallery/{media}', [ProjectController::class, 'destroyGalleryImage'])->name('projects.gallery.destroy');
    Route::resource('professional-references', ProfessionalReferenceController::class);

    Route::resource('testimonials', TestimonialController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::delete('testimonials/{testimonial}/video', [TestimonialController::class, 'destroyVideo'])->name('testimonials.video.destroy');
    Route::resource('contacts', ContactController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::resource('engagements', EngagementController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::resource('cv-downloads', CvDownloadController::class)->only(['index', 'show', 'destroy']);
    Route::resource('subscribers', SubscriberController::class)->only(['index', 'destroy']);

    Route::resource('appointments', AppointmentController::class)->only(['index', 'show', 'destroy']);
    Route::patch('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::patch('appointments/{appointment}/decline', [AppointmentController::class, 'decline'])->name('appointments.decline');
    Route::resource('appointment-types', AppointmentTypeController::class)->except('show');
    Route::prefix('availability')->name('availability.')->group(function () {
        Route::get('/', [AvailabilityController::class, 'index'])->name('index');
        Route::post('rules', [AvailabilityController::class, 'storeRule'])->name('rules.store');
        Route::post('blocked-periods', [AvailabilityController::class, 'storeBlockedPeriod'])->name('blocked-periods.store');
        Route::delete('{schedule}', [AvailabilityController::class, 'destroy'])->name('destroy');
    });
});
