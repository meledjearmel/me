<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\NamesAiClient;
use App\Services\Ai\ContentRevisions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('propose-revision')]
#[Description('Propose une modification d\'un contenu déjà publié (project, post, experience, education, certification, uses_item, skill, service) ou de la page « Now » (now_page, sans id). Rien ne change sur le site : le développeur voit l\'avant/après dans l\'administration et applique ou rejette. N\'envoyer que les champs modifiés, en français (fr, mêmes noms que les outils de brouillon) et en anglais (en, mêmes noms que les outils de traduction) : le contenu étant en ligne dans les deux langues, chaque champ traduit modifié en français doit l\'être aussi en anglais. Lire le contenu d\'abord (get-project, get-post, get-content). Pour un brouillon, utiliser plutôt les outils de brouillon.')]
class ProposeRevisionTool extends Tool
{
    use NamesAiClient;

    public function handle(Request $request, ContentRevisions $revisions): Response
    {
        $request->validate([
            'type' => ['required', 'in:'.implode(',', $revisions->typeNames())],
            'id' => ['nullable', 'integer', 'required_unless:type,'.ContentRevisions::NOW_PAGE],
            'fr' => ['nullable', 'array'],
            'en' => ['nullable', 'array'],
            'summary' => ['required', 'string', 'max:1000'],
        ]);

        $revision = $revisions->propose(
            $request->get('type'),
            $request->get('id'),
            $request->get('fr') ?? [],
            $request->get('en') ?? [],
            $request->get('summary'),
            $this->aiClient($request),
        );

        return Response::text("Révision #{$revision->id} proposée ; rien n'a changé sur le site. Le développeur la relit et l'applique ici : ".route('admin.revisions.show', $revision));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(app(ContentRevisions::class)->typeNames())->description('Type du contenu publié.')->required(),
            'id' => $schema->integer()->description('Identifiant du contenu (absent pour now_page).'),
            'fr' => $schema->object()->description('Champs modifiés en français, ex. {"tagline": "…"} ; pour now_page : {"now_content": "…"} (texte brut, 5 000 caractères max., mentions acceptées).'),
            'en' => $schema->object()->description('Les mêmes champs en anglais ; les listes (decisions, key_figure_labels, highlights) complètes et dans le même ordre que le français.'),
            'summary' => $schema->string()->description('Ce qui change et pourquoi, en une à trois phrases, pour le développeur.')->required(),
        ];
    }
}
