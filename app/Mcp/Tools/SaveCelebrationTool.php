<?php

namespace App\Mcp\Tools;

use App\Models\Celebration;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('save-celebration')]
#[Description('Crée une surprise en brouillon (bonne nouvelle annoncée au hasard par Armi, l\'avatar du site) : elle reste désactivée, invisible des visiteurs, jusqu\'à ce que le développeur la relise et l\'active dans l\'administration. Ne modifie jamais une surprise existante : pour cela, propose une révision (propose-revision, type « celebration »).')]
class SaveCelebrationTool extends Tool
{
    public function handle(Request $request): Response
    {
        $french = Validator::make([
            ...$request->only(array_keys(Celebration::DRAFT_RULES)),
            'message' => $request->get('message_fr'),
            'button_label' => $request->get('button_label_fr'),
        ], Celebration::DRAFT_RULES);

        $english = Validator::make($request->only(['message_en', 'button_label_en']), [
            'message_en' => Celebration::DRAFT_RULES['message'],
            'button_label_en' => Celebration::DRAFT_RULES['button_label'],
        ]);

        if ($french->fails() || $english->fails()) {
            return Response::error(implode(' ', [...$french->errors()->all(), ...$english->errors()->all()]));
        }

        $data = $french->validated();
        $celebration = new Celebration([
            ...collect($data)->except(['message', 'button_label'])->all(),
            'message' => ['fr' => $data['message'], 'en' => $request->get('message_en')],
            'button_label' => ['fr' => $data['button_label'], 'en' => $request->get('button_label_en')],
            'is_active' => false,
        ]);
        $celebration->save();

        return Response::text("Surprise créée en brouillon (identifiant {$celebration->id}) : désactivée et invisible des visiteurs jusqu'à ce que le développeur la relise et l'active ici : ".route('admin.celebrations.edit', $celebration));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'message_fr' => $schema->string()->description('La bonne nouvelle, en français, à la première personne et en une ou deux phrases (280 caractères max.).')->required(),
            'message_en' => $schema->string()->description('Le même message en anglais.')->required(),
            'button_label_fr' => $schema->string()->description('Texte du bouton de félicitations, court (40 caractères max.), ex. « Bravo ! ».')->required(),
            'button_label_en' => $schema->string()->description('Le même en anglais, ex. « Congrats! ».')->required(),
            'congratulated_for' => $schema->string()->description('Ce qui est fêté, pour le suivi dans l\'administration (150 caractères max.).')->required(),
            'starts_at' => $schema->string()->description('Premier jour d\'affichage, AAAA-MM-JJ ; vide = dès l\'activation.'),
            'ends_at' => $schema->string()->description('Dernier jour d\'affichage, AAAA-MM-JJ ; vide = sans fin.'),
            'weight' => $schema->integer()->description('Poids au tirage parmi les surprises affichables, de 1 à 100 (1 par défaut).'),
            'chance_percent' => $schema->integer()->description('Probabilité d\'apparaître lors d\'une visite, en % (33 par défaut).'),
            'delay_seconds' => $schema->integer()->description('Délai avant l\'apparition, en secondes (9 par défaut).'),
            'display_seconds' => $schema->integer()->description('Durée d\'affichage, en secondes, de 5 à 120 (15 par défaut).'),
            'snooze_days' => $schema->integer()->description('Jours avant de reproposer une surprise au même visiteur (7 par défaut).'),
        ];
    }
}
