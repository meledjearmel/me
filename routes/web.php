<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CongratulationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\TestimonialSubmissionController;
use App\Http\Middleware\LogPageVisit;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ShareSitePublicData;
use Illuminate\Support\Facades\Route;

Route::pattern('locale', implode('|', SetLocale::LOCALES));

Route::get('/', function () {
    $locale = request()->getPreferredLanguage(SetLocale::LOCALES) ?? SetLocale::LOCALES[0];

    return redirect("/{$locale}");
})->name('home.redirect');

Route::prefix('{locale}')->middleware(['locale', LogPageVisit::class, ShareSitePublicData::class])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('about', [AboutController::class, 'index'])->name('about');
    Route::get('skills', [SkillController::class, 'index'])->name('skills');
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('contact', [ContactController::class, 'index'])->name('contact.index');
    Route::post('contact', [ContactController::class, 'store'])->name('contact.store');
    Route::get('testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
    Route::post('testimonials', [TestimonialSubmissionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('testimonials.store');
    Route::post('engagements', [EngagementController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('engagements.store');
    Route::post('congratulations', [CongratulationController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('congratulations.store');
    Route::post('chat', [ChatController::class, 'store'])
        ->middleware('throttle:chat')
        ->name('chat.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
