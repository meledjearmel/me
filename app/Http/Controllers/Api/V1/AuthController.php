<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\TwoFactorChallengeRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Exceptions\Google2FAException;

/**
 * @tags Authentification
 */
class AuthController extends Controller
{
    /** Durée de validité d'un challenge 2FA, avant de devoir se reconnecter. */
    private const int CHALLENGE_TTL_MINUTES = 5;

    /**
     * Connexion
     *
     * Échange l'email et le mot de passe contre un jeton d'accès à envoyer
     * ensuite en `Authorization: Bearer {token}`. Sur un compte protégé par la double
     * authentification, renvoie plutôt un défi (`two_factor: true`, `challenge`) à
     * transmettre à `POST /auth/two-factor-challenge` avec le code de l'application
     * ou un code de secours.
     *
     * @unauthenticated
     *
     * @response array{token: string, user: UserResource}
     * @response array{two_factor: true, challenge: string}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $challenge = Str::random(40);

            Cache::put("api-2fa-challenge:{$challenge}", $user->id, now()->addMinutes(self::CHALLENGE_TTL_MINUTES));

            return response()->json(['two_factor' => true, 'challenge' => $challenge]);
        }

        return response()->json([
            'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Confirmer la double authentification
     *
     * Termine la connexion démarrée par `POST /auth/login` sur un compte protégé par la
     * double authentification : `code` (code de l'application) ou `recovery_code` (usage
     * unique, remplacé après usage). Le `challenge` expire au bout de 5 minutes.
     *
     * @unauthenticated
     *
     * @response array{token: string, user: UserResource}
     */
    public function twoFactorChallenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $cacheKey = 'api-2fa-challenge:'.$request->validated('challenge');
        $userId = Cache::get($cacheKey);

        if ($userId === null) {
            throw ValidationException::withMessages([
                'challenge' => __('Ce défi a expiré, reconnectez-vous.'),
            ]);
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            throw ValidationException::withMessages([
                'challenge' => __('Ce défi a expiré, reconnectez-vous.'),
            ]);
        }

        if (! $this->hasValidCode($user, $request) && ! $this->consumeValidRecoveryCode($user, $request)) {
            throw ValidationException::withMessages([
                'code' => __('Le code fourni est invalide.'),
            ]);
        }

        Cache::forget($cacheKey);

        return response()->json([
            'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    private function hasValidCode(User $user, TwoFactorChallengeRequest $request): bool
    {
        $code = $request->validated('code');

        if (blank($code)) {
            return false;
        }

        try {
            return app(TwoFactorAuthenticationProvider::class)->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                $code,
            );
        } catch (Google2FAException) {
            // Secret mal formé (ou code non numérique) : un 500 serait trompeur, le code est simplement invalide.
            return false;
        }
    }

    private function consumeValidRecoveryCode(User $user, TwoFactorChallengeRequest $request): bool
    {
        $recoveryCode = $request->validated('recovery_code');

        if (blank($recoveryCode)) {
            return false;
        }

        $validCode = collect($user->recoveryCodes())->first(fn (string $code): bool => hash_equals($code, $recoveryCode));

        if ($validCode === null) {
            return false;
        }

        $user->replaceRecoveryCode($validCode);

        return true;
    }

    /**
     * Utilisateur connecté
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Déconnexion
     *
     * Révoque le jeton utilisé pour cette requête.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
