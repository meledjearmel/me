<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

/**
 * Le compte administrateur vu par Passport (connecteurs Claude et ChatGPT du serveur MCP).
 * Même table que User, mais un modèle à part : User porte déjà les jetons Sanctum (app
 * mobile, Claude Code, Codex), dont le trait entre en conflit avec celui de Passport.
 */
class OAuthUser extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
}
