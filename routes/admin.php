<?php

use App\Http\Controllers\Admin\AiAssistController;
use App\Http\Controllers\Admin\CelebrationController;
use App\Http\Controllers\Admin\CongratulationController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\EngagementController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\JobProfileController;
use App\Http\Controllers\Admin\MusicGenreController;
use App\Http\Controllers\Admin\ProfessionalReferenceController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\TechnologyCategoryController;
use App\Http\Controllers\Admin\TechnologyController;
use App\Http\Controllers\Admin\TechnologyIconController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\TrackController;
use App\Http\Controllers\Admin\TrashController;
use App\Models\JobProfile;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile/music', [ProfileController::class, 'destroyMusic'])->name('profile.music.destroy');

    Route::prefix('ai')->name('ai.')->middleware('throttle:ai-assist')->group(function () {
        Route::post('translate', [AiAssistController::class, 'translate'])->name('translate');
        Route::post('improve', [AiAssistController::class, 'improve'])->name('improve');
        Route::post('describe-technology', [AiAssistController::class, 'describeTechnology'])->name('describe-technology');
    });

    Route::prefix('trash')->name('trash.')->group(function () {
        Route::get('/', [TrashController::class, 'index'])->name('index');
        Route::patch('{type}/{id}', [TrashController::class, 'restore'])->whereNumber('id')->name('restore');
        Route::delete('{type}/{id}', [TrashController::class, 'forceDelete'])->whereNumber('id')->name('force-delete');
    });

    Route::resource('domains', DomainController::class);
    Route::resource('music-genres', MusicGenreController::class)->except('show');
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
    Route::delete('projects/{project}/cover', [ProjectController::class, 'destroyCover'])->name('projects.cover.destroy');
    Route::delete('projects/{project}/gallery/{media}', [ProjectController::class, 'destroyGalleryImage'])->name('projects.gallery.destroy');
    Route::resource('professional-references', ProfessionalReferenceController::class);

    Route::resource('testimonials', TestimonialController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::resource('contacts', ContactController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::resource('engagements', EngagementController::class)->only(['index', 'show', 'update', 'destroy']);
});
