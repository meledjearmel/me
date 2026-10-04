<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AppointmentBookingController;
use App\Http\Controllers\AppointmentCancellationController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogFeedController;
use App\Http\Controllers\CelebrationCongratulationController;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CongratulationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CvDownloadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LlmsTxtController;
use App\Http\Controllers\LocaleRedirectController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NowController;
use App\Http\Controllers\PostShareImageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RobotsTxtController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\TestimonialSubmissionController;
use App\Http\Controllers\UsesController;
use App\Http\Middleware\CaptureTrafficSource;
use App\Http\Middleware\LogPageVisit;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ShareSitePublicData;
use Illuminate\Support\Facades\Route;

Route::pattern('locale', implode('|', SetLocale::LOCALES));

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('llms.txt', LlmsTxtController::class)->name('llms');
Route::get('robots.txt', RobotsTxtController::class)->name('robots');

// La query string est conservée : un lien de campagne (?ref=linkedin) garde sa provenance.
Route::get('/', function () {
    $query = request()->getQueryString();

    return redirect('/'.SetLocale::fromBrowser(request()).($query ? '?'.$query : ''));
})->name('home.redirect');

Route::prefix('{locale}')->middleware(['locale', CaptureTrafficSource::class, LogPageVisit::class, ShareSitePublicData::class])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('about', [AboutController::class, 'index'])->name('about');
    Route::get('skills', [SkillController::class, 'index'])->name('skills');
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('blog/feed', BlogFeedController::class)->name('blog.feed');
    Route::get('blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
    // Image lue par les réseaux sociaux, pas une page : elle ne compte pas dans l'audience.
    Route::get('blog/{post:slug}/share.png', PostShareImageController::class)
        ->middleware('throttle:60,1')
        ->withoutMiddleware(LogPageVisit::class)
        ->name('blog.share-image');
    // Appel de la fenêtre de recherche, pas une page : il ne compte pas dans l'audience.
    Route::get('search', SearchController::class)
        ->middleware('throttle:60,1')
        ->withoutMiddleware(LogPageVisit::class)
        ->name('search');
    Route::post('newsletter', [NewsletterController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('newsletter.store');
    Route::get('newsletter/{token}/confirm', [NewsletterController::class, 'confirm'])
        ->middleware('throttle:30,1')
        ->name('newsletter.confirm');
    Route::get('newsletter/{token}/unsubscribe', [NewsletterController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('newsletter.unsubscribe');
    Route::post('newsletter/{token}/unsubscribe', [NewsletterController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('newsletter.unsubscribe.store');
    Route::get('uses', [UsesController::class, 'index'])->name('uses');
    Route::get('certifications', [CertificationController::class, 'index'])->name('certifications');
    Route::get('now', NowController::class)->name('now');
    Route::get('contact', [ContactController::class, 'index'])->name('contact.index');
    Route::post('contact', [ContactController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('contact.store');
    Route::post('cv', CvDownloadController::class)
        ->middleware('throttle:10,1')
        ->name('cv.download');
    Route::get('testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
    Route::post('testimonials', [TestimonialSubmissionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('testimonials.store');
    Route::get('appointments', [AppointmentBookingController::class, 'index'])->name('appointments.index');
    Route::get('appointments/slots', [AppointmentBookingController::class, 'slots'])
        ->middleware('throttle:60,1')
        ->name('appointments.slots');
    Route::post('appointments', [AppointmentBookingController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('appointments.store');
    Route::get('appointments/{token}/cancel', [AppointmentCancellationController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('appointments.cancel');
    Route::post('appointments/{token}/cancel', [AppointmentCancellationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('appointments.cancel.store');
    Route::post('engagements', [EngagementController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('engagements.store');
    Route::post('congratulations', [CongratulationController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('congratulations.store');
    Route::post('celebrations/{celebration}/congratulations', CelebrationCongratulationController::class)
        ->middleware('throttle:60,1')
        ->name('celebrations.congratulate');
    Route::post('chat', [ChatController::class, 'store'])
        ->middleware('throttle:chat')
        ->name('chat.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';

// Lien sans langue (/about) : redirigé vers la page dans la langue du visiteur, sinon 404.
// Toutes les méthodes : une requête POST ou DELETE vers une adresse inconnue reste une 404, pas une 405.
Route::any('{fallbackPlaceholder}', LocaleRedirectController::class)
    ->where('fallbackPlaceholder', '.*')
    ->fallback();
