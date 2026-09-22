<?php

use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\JobProfileController;
use App\Http\Controllers\Admin\ProfessionalReferenceController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\TechnologyController;
use App\Http\Controllers\Admin\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('domains', DomainController::class)->except('show');
    Route::resource('technologies', TechnologyController::class)->except('show');
    Route::resource('job-profiles', JobProfileController::class)->except('show');
    Route::resource('skills', SkillController::class)->except('show');
    Route::resource('educations', EducationController::class)->except('show');
    Route::resource('experiences', ExperienceController::class)->except('show');
    Route::resource('projects', ProjectController::class)->except('show');
    Route::resource('professional-references', ProfessionalReferenceController::class)->except('show');

    Route::resource('testimonials', TestimonialController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::resource('contacts', ContactController::class)->only(['index', 'edit', 'update', 'destroy']);
});
