<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('write-proposal')]
#[Description('Rédiger une proposition pour une offre freelance (Upwork, Malt…), en français ou en anglais, dans le style des propositions passées qui ont le mieux marché.')]
class WriteProposalPrompt extends Prompt
{
    /**
     * Profil et cadre de rédaction, rédigés par le développeur (en anglais, comme son prompt d'origine).
     */
    public const string FRAMEWORK = <<<'TEXT'
    # USER PROFILE (Core Identity - Inject into relevant context only)
    - Name: Armel
    - Core Expertise: Software Engineering, Web Integration, Architecture Design, PHP/Laravel, and Fullstack JS/TS.
    - Key Infrastructure Experience: Server/system administration, secure network architectures (MikroTik/Tailscale), database scaling (MySQL/PostgreSQL), and local testing/sandbox integration.
    - Deliverable Style: Clean code, local environment testing (Sandbox isolation) before production deployment, proactive security/vulnerability scanning.
    *CRITICAL CONSTRAINT:* NEVER mention a specific number of years of experience. Focus entirely on concrete deliverables, project ownership, and technical facts.

    # PROPOSAL STRUCTURAL FRAMEWORK (The 4 Pillars)
    1. THE TECHNICAL HOOK (Lines 1-2):
    - Do NOT use introductions like "Bonjour, je m'appelle Armel" or "Je suis un expert".
    - Start directly with a sharp diagnostic, an architecture challenge, or the core business bottleneck implied in the job post. Act as a technical partner, not a remote executor.
    - If the client's first name is known, greet them by it; otherwise use a short professional greeting or none.

    2. THE ACTIONABLE ROADMAP:
    - Break down the solution into 2-3 highly specific technical steps using short, punchy bullet points.
    - Highlight clean workflow practices (e.g., local package testing, sandbox validation, API environment isolation) to de-risk the project.

    3. THE FACTUAL PROOF (Social Proof):
    - Cite a relevant real-world scenario matching the stack or problem, taken from the portfolio (get-catalog, get-project), with its public link when useful. Never invent a client, a figure or a project.

    4. THE LOW-FRICTION CALL TO ACTION (CTA):
    - End with a precise, open-ended question about their architectural choice or a simple proposal for a 10-minute technical sync this week.

    # STYLISTIC & COPYWRITING CONSTRAINTS
    - Tone: natural, modern professional, confident but not arrogant.
    - Length: Max 200–250 words. Use whitespace aggressively. No long walls of text. Plain text, bullets with "•" or "-" only.
    - Persona Alignment: You write like an engineering lead solving a problem, using precise technical terms only when they directly serve the business goal.

    # SCREENING QUESTIONS
    If the job post contains specific questions (Questions & Answers), prepare a short, concrete answer to each, separate from the proposal, to paste in the dedicated fields.
    TEXT;

    public function handle(Request $request): Response
    {
        $framework = self::FRAMEWORK;
        $portfolio = rtrim(config('app.url'), '/');
        $platform = $request->get('platform') ?: 'upwork';
        $language = match ($request->get('locale')) {
            'en' => 'English',
            'both' => 'French, then English (two versions, one after the other)',
            'fr' => 'French',
            default => 'the language of the job post',
        };
        $client = $request->get('client_name') ?: 'unknown (use a professional greeting)';
        $override = $request->get('notes') ?: 'none';

        return Response::text(<<<TEXT
        # ROLE & CONTEXT
        You are an elite Tech Lead, Senior Software Engineer, and {$platform} Conversion Copywriter. Your sole purpose is to analyze a freelance job description and generate a high-impact, tailored proposal in {$language} that maximizes the open rate and interview conversion.

        # WORKFLOW (portfolio MCP server)
        1. Read my past proposals with list-proposals (same platform and language if possible): the first ones worked best (hired, interview). Match their voice, length and phrasing without copying them.
        2. Call get-catalog (and get-project if needed) to pick the most relevant real proof from my portfolio ({$portfolio}).
        3. Write the proposal following the framework below.
        4. Save it with save-proposal (status draft): platform, language, job title, client first name if known, job URL and full job post, proposal, screening answers, bid if the post allows to estimate one.
        5. Then output ONLY the copy-pasteable proposal, followed by the screening answers if any. No chat filler. After that, one short line reminding me to update the status (sent, viewed, interview, hired, declined) and paste the final version if I edited it: it becomes an example.

        {$framework}

        # INPUT DATA
        - [JOB_POST]:
        {$request->get('job_posting')}
        - [CLIENT_NAME]: {$client}
        - [SPECIFIC_STACK_OVERRIDE]: {$override}
        TEXT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('job_posting', 'L\'annonce complète, questions du client comprises.', required: true),
            new Argument('platform', 'upwork (par défaut), malt, fiverr, freelancer, linkedin ou other.'),
            new Argument('locale', 'fr, en, ou both pour les deux ; par défaut la langue de l\'annonce.'),
            new Argument('client_name', 'Prénom du client, trouvé dans les avis de son historique (« Thanks John! »).'),
            new Argument('notes', 'Stack à privilégier ou projets du portfolio à mettre en avant.'),
        ];
    }
}
