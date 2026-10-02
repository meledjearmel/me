<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ShareSitePublicData;
use App\Models\Profile;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureApiDocs();
        $this->configureRateLimiting();
        $this->configureErrorPages();
        $this->configureMails();
    }

    /**
     * Signature des emails (en-tête et pied de page) : le nom du propriétaire,
     * le portfolio dans la langue de l'email et ses liens publics.
     */
    protected function configureMails(): void
    {
        View::composer('mail::message', function ($view): void {
            $profile = Profile::query()->first();
            $siteUrl = rtrim((string) config('app.url'), '/');

            $view->with('owner', [
                'name' => $profile?->name ?? config('app.name'),
                'location' => $profile?->location,
                'portfolio' => $siteUrl.'/'.app()->getLocale(),
                'portfolioLabel' => preg_replace('#^https?://#', '', $siteUrl),
                'links' => collect($profile?->social_links ?? [])
                    ->filter()
                    ->mapWithKeys(fn (string $url, string $network): array => [
                        ['github' => 'GitHub', 'linkedin' => 'LinkedIn'][$network] ?? ucfirst($network) => $url,
                    ])
                    ->all(),
            ]);
        });
    }

    /**
     * Pages d'erreur du site public, aux couleurs du site et dans la langue
     * du visiteur. L'admin et l'API gardent les réponses de Laravel ; en
     * débogage, les erreurs serveur gardent leur page de diagnostic.
     */
    protected function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $status = $response->statusCode();
            $request = $response->request;

            if (! in_array($status, [403, 404, 429, 500, 503], true)
                || $request->is(
                    'admin', 'admin/*', 'settings', 'settings/*', 'api/*', 'dashboard',
                    'login', 'logout', 'register', 'forgot-password', 'reset-password*',
                    'two-factor*', 'user/*', 'email/*',
                )
                || ($status >= 500 && config('app.debug'))
                // La coque publique a besoin du profil : sans lui, la page de Laravel prend le relais.
                || ! Profile::query()->exists()) {
                return null;
            }

            app()->setLocale(SetLocale::fromRequest($request));
            ShareSitePublicData::share();

            return $response->render('public/error', ['status' => $status])->withSharedData();
        });
    }

    /**
     * Limites des fonctionnalités IA : par minute et par jour, par visiteur ou
     * utilisateur connecté, plus un plafond global pour le chat public afin de
     * ne jamais vider le quota gratuit des fournisseurs.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('chat', fn (Request $request): array => [
            Limit::perMinute(config('ai.chat.limits.per_minute'))->by($request->ip()),
            Limit::perDay(config('ai.chat.limits.per_day'))->by($request->ip()),
            Limit::perDay(config('ai.chat.limits.global_per_day'))->by('chat-global'),
        ]);

        RateLimiter::for('ai-assist', fn (Request $request): array => [
            Limit::perMinute(config('ai.text_assist.limits.per_minute'))->by($request->user()?->id ?: $request->ip()),
            Limit::perDay(config('ai.text_assist.limits.per_day'))->by($request->user()?->id ?: $request->ip()),
        ]);

        RateLimiter::for('technology-icons', fn (Request $request): Limit => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Documente l'authentification par jeton Bearer (Sanctum) de l'API mobile.
     */
    protected function configureApiDocs(): void
    {
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi): void {
            $openApi->secure(SecurityScheme::http('bearer'));
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        // Les props Inertia consomment directement les ressources : pas d'enveloppe "data" d'API REST.
        JsonResource::withoutWrapping();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
