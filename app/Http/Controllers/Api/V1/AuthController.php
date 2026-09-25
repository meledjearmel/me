<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @tags Authentification
 */
class AuthController extends Controller
{
    /**
     * Connexion
     *
     * Échange l'email et le mot de passe contre un jeton d'accès à envoyer
     * ensuite en `Authorization: Bearer {token}`.
     * Les comptes protégés par la double authentification ne sont pas encore pris en charge.
     *
     * @unauthenticated
     *
     * @response array{token: string, user: UserResource}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($user->two_factor_confirmed_at !== null) {
            throw ValidationException::withMessages([
                'email' => __('La double authentification n\'est pas encore prise en charge par l\'API.'),
            ]);
        }

        return response()->json([
            'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
            'user' => new UserResource($user),
        ]);
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
