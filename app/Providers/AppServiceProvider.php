<?php

namespace App\Providers;

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
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
    }

    /**
     * Limites de l'assistant : par minute et par jour et par visiteur, plus un
     * plafond global pour ne jamais vider le quota gratuit des fournisseurs.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('chat', fn (Request $request): array => [
            Limit::perMinute(config('ai.chat.limits.per_minute'))->by($request->ip()),
            Limit::perDay(config('ai.chat.limits.per_day'))->by($request->ip()),
            Limit::perDay(config('ai.chat.limits.global_per_day'))->by('chat-global'),
        ]);
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
