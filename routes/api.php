<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DomainController;
use App\Http\Controllers\Api\V1\EducationController;
use App\Http\Controllers\Api\V1\EngagementController;
use App\Http\Controllers\Api\V1\ExperienceController;
use App\Http\Controllers\Api\V1\JobProfileController;
use App\Http\Controllers\Api\V1\ProfessionalReferenceController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\SkillController;
use App\Http\Controllers\Api\V1\TechnologyController;
use App\Http\Controllers\Api\V1\TestimonialController;
use App\Models\Profile;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::apiResource('contacts', ContactController::class)->except('store');
        Route::apiResource('engagements', EngagementController::class)->except('store');
        Route::apiResource('testimonials', TestimonialController::class)->except('store');

        Route::apiResource('domains', DomainController::class);
        Route::apiResource('technologies', TechnologyController::class);
        Route::apiResource('job-profiles', JobProfileController::class);
        Route::apiResource('skills', SkillController::class);
        Route::apiResource('educations', EducationController::class);
        Route::apiResource('professional-references', ProfessionalReferenceController::class);
        Route::apiResource('experiences', ExperienceController::class);
        Route::apiResource('projects', ProjectController::class);

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('profile/music', [ProfileController::class, 'destroyMusic'])->name('profile.music.destroy');
        Route::delete('profile/cv/{locale}', [ProfileController::class, 'destroyCv'])
            ->whereIn('locale', Profile::CV_LOCALES)
            ->name('profile.cv.destroy');
    });
});
