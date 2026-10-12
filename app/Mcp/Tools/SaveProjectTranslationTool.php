<?php

namespace App\Mcp\Tools;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Services\Ai\ProjectDraftWriter;
use App\Services\PostMentions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Enregistre la traduction anglaise d\'un brouillon de projet, une fois son français validé par le développeur. Le français n\'est pas modifié et le brouillon n\'est pas publié. Les choix techniques et les chiffres clés sont traduits dans le même ordre que get-project les renvoie.')]
#[IsIdempotent]
class SaveProjectTranslationTool extends Tool
{
    public function handle(Request $request, PostMentions $mentions): Response
    {
        $project = Project::query()->find($request->get('id'));

        if ($project?->status !== ProjectStatus::Draft) {
            return Response::error('Seul un brouillon existant peut être traduit par cet outil.');
        }

        $decisions = $project->decisions ?? [];
        $figures = $project->key_figures ?? [];

        $data = $request->validate([
            'id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'role' => ['nullable', 'string', 'max:120'],
            'client' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:120'],
            'context' => ['required', 'string'],
            'challenges' => ['nullable', 'string'],
            'realization' => ['required', 'string'],
            'result' => ['required', 'string'],
            'decisions' => ['array', 'size:'.count($decisions)],
            'decisions.*.choice' => ['required', 'string', 'max:160'],
            'decisions.*.reason' => ['required', 'string', 'max:600'],
            'key_figure_labels' => ['array', 'size:'.count($figures)],
            'key_figure_labels.*' => ['required', 'string', 'max:80'],
        ], [
            'decisions.size' => 'Traduisez les :size choix techniques, dans l\'ordre.',
            'key_figure_labels.size' => 'Traduisez les :size libellés de chiffres clés, dans l\'ordre.',
        ]);

        foreach (ProjectDraftWriter::TRANSLATABLE as $field) {
            if (! filled($project->getTranslation($field, 'fr', false)) || ! filled($data[$field] ?? null)) {
                continue;
            }

            $text = in_array($field, ProjectDraftWriter::WITH_MENTIONS, true)
                ? $mentions->withoutUnknownMentions($data[$field])['text']
                : PostMentions::plain($data[$field]);

            $project->setTranslation($field, 'en', $text);
        }

        $project->decisions = collect($decisions)->values()->map(fn (array $decision, int $index): array => [
            'choice' => [...$decision['choice'], 'en' => $data['decisions'][$index]['choice']],
            'reason' => [...$decision['reason'], 'en' => $data['decisions'][$index]['reason']],
        ])->all();
        $project->key_figures = collect($figures)->values()->map(fn (array $figure, int $index): array => [
            ...$figure,
            'label' => [...$figure['label'], 'en' => $data['key_figure_labels'][$index]],
        ])->all();
        $project->save();

        return Response::text("Traduction anglaise du brouillon #{$project->id} enregistrée. À relire puis publier dans l'administration : ".route('admin.projects.edit', $project));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $text = fn (string $description) => $schema->string()->description($description);

        return [
            'id' => $schema->integer()->description('Identifiant du brouillon.')->required(),
            'title' => $text('Titre en anglais.')->required(),
            'tagline' => $text('Accroche en anglais (160 caractères max.).'),
            'role' => $text('Rôle en anglais (120 max.).'),
            'client' => $text('Client en anglais (120 max.).'),
            'platform' => $text('Plateforme en anglais (120 max.).'),
            'context' => $text('Contexte en anglais. Garder les jetons de mention @[Libellé](type:id) : seul le libellé se traduit.')->required(),
            'challenges' => $text('Défis en anglais.'),
            'realization' => $text('Réalisation en anglais.')->required(),
            'result' => $text('Résultat en anglais.')->required(),
            'decisions' => $schema->array()->items($schema->object([
                'choice' => $text('Le choix, en anglais (160 max.).')->required(),
                'reason' => $text('Pourquoi, en anglais (600 max.).')->required(),
            ]))->description('Tous les choix techniques, dans le même ordre qu\'en français.'),
            'key_figure_labels' => $schema->array()->items($schema->string())->description('Libellés anglais de tous les chiffres clés, dans le même ordre (80 max. chacun).'),
        ];
    }
}
