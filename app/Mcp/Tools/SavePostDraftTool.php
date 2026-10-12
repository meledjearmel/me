<?php

namespace App\Mcp\Tools;

use App\Enums\PublicationStatus;
use App\Mcp\Concerns\NamesAiClient;
use App\Models\Post;
use App\Services\Ai\PostDraftWriter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('save-post-draft')]
#[Description('Crée un brouillon d\'article du blog en français (sans id), ou modifie seulement les champs envoyés d\'un brouillon existant (avec id). Le développeur relit l\'aperçu, fait traduire puis publie lui-même ; un article publié n\'est jamais modifié ici.')]
class SavePostDraftTool extends Tool
{
    use NamesAiClient;

    private const array FIELDS = ['title', 'slug', 'excerpt', 'body', 'tags', 'series', 'series_position'];

    public function handle(Request $request, PostDraftWriter $writer): Response
    {
        $post = $request->get('id') !== null ? Post::query()->find($request->get('id')) : new Post;

        if ($request->get('id') !== null && $post?->status !== PublicationStatus::Draft) {
            return Response::error('Seul un brouillon d\'article existant peut être modifié.');
        }

        if (! $post->exists) {
            $post->drafted_by = $this->aiClient($request);
        }

        $changes = collect($request->all())->only(self::FIELDS)->all();
        $input = $post->exists ? [...$writer->toInput($post), ...$changes] : $changes;
        $notes = $writer->save($post, $input);

        return Response::text(implode("\n", [
            "Brouillon d'article #{$post->id} enregistré.",
            "Aperçu (lien valable quelques heures) : {$post->previewUrl()}",
            'À relire, traduire et publier dans l\'administration : '.route('admin.posts.edit', $post),
            ...$notes,
        ]));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Brouillon existant à modifier ; absent pour en créer un (title, slug et body sont alors obligatoires).'),
            'title' => $schema->string()->description('Titre (255 caractères max.).'),
            'slug' => $schema->string()->description('Identifiant d\'URL en minuscules et tirets, unique (pas « feed »).'),
            'excerpt' => $schema->string()->description('Résumé de une à deux phrases (300 max.), affiché dans les listes et les partages.'),
            'body' => $schema->string()->description('Contenu en HTML simple, sans <html> ni <body>. Balises : p, h2, h3, h4, strong, em, u, s, mark, code, pre (avec <code class="language-php"> pour colorer), blockquote, ul, ol, li, a, table, tr, th, td, hr, sub, sup, img. Pas de style ni de script. Pour citer un élément du site, écrire son jeton de mention (get-catalog), ex. @[App Station](project:12).'),
            'tags' => $schema->array()->max(8)->items($schema->string())->description('Noms des tags (8 max., 40 caractères chacun) : reprendre ceux de get-catalog quand ils conviennent ; un nom nouveau crée le tag. Liste complète.'),
            'series' => $schema->string()->description('Nom exact d\'une série de get-catalog (post_series) si l\'article en prolonge une ; un nom nouveau crée la série ; vide pour sortir l\'article d\'une série.'),
            'series_position' => $schema->integer()->description('Place dans la série (1 à 99), obligatoire avec series : en général next_position de la série.'),
        ];
    }
}
