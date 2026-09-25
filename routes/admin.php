<?php

use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\EngagementController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\JobProfileController;
use App\Http\Controllers\Admin\ProfessionalReferenceController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\TechnologyController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Models\Profile;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile/music', [ProfileController::class, 'destroyMusic'])->name('profile.music.destroy');
    Route::delete('profile/cv/{locale}', [ProfileController::class, 'destroyCv'])
        ->whereIn('locale', Profile::CV_LOCALES)
        ->name('profile.cv.destroy');

    Route::resource('domains', DomainController::class);
    Route::resource('technologies', TechnologyController::class);
    Route::resource('job-profiles', JobProfileController::class);
    Route::resource('skills', SkillController::class);
    Route::resource('educations', EducationController::class);
    Route::resource('experiences', ExperienceController::class);
    Route::resource('projects', ProjectController::class);
    Route::resource('professional-references', ProfessionalReferenceController::class);

    Route::resource('testimonials', TestimonialController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::resource('contacts', ContactController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::resource('engagements', EngagementController::class)->only(['index', 'show', 'update', 'destroy']);
});
