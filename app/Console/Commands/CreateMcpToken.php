<?php

namespace App\Console\Commands;

use App\Http\Middleware\RejectMcpTokens;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Crée le jeton Sanctum qu'un client MCP (Claude Code, Codex…) envoie en
 * « Authorization: Bearer » au serveur /mcp/portfolio. Limité au MCP (refusé par l'API), il n'est affiché qu'une fois.
 */
#[Signature('mcp:token {email? : Compte administrateur (le premier par défaut)} {--name=MCP : Nom du jeton, visible dans l’administration}')]
#[Description('Crée un jeton d’accès au serveur MCP du portfolio')]
class CreateMcpToken extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $user = $email ? User::query()->where('email', $email)->first() : User::query()->oldest('id')->first();

        if ($user === null) {
            $this->error('Aucun compte trouvé.');

            return self::FAILURE;
        }

        $token = $user->createToken($this->option('name'), [RejectMcpTokens::ABILITY])->plainTextToken;

        $this->info("Jeton créé pour {$user->email} (il ne sera plus affiché) :");
        $this->line($token);
        $this->newLine();
        $this->line('Claude Code : claude mcp add --transport http portfolio '.url('/mcp/portfolio').' --header "Authorization: Bearer '.$token.'"');

        return self::SUCCESS;
    }
}
