<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\NamesAiClient;
use App\Services\Ai\ContentRevisions;
use App\Services\EstimatorConfigurator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('save-estimator-item')]
#[Description('Crée un élément de la grille de l\'estimateur (type de projet ou option), masqué aux visiteurs tant que le développeur ne l\'a pas activé dans l\'administration. Avec un id, propose une modification de l\'élément (seuls les champs envoyés, avec summary) : c\'est une révision que le développeur relit et applique, rien ne change avant. Pour plusieurs éléments ou les réglages, préfère configure-estimator.')]
class SaveEstimatorItemTool extends Tool
{
    use NamesAiClient;

    public function handle(Request $request, EstimatorConfigurator $configurator, ContentRevisions $revisions): Response
    {
        $entry = $request->only([
            'id', 'kind', 'name_fr', 'name_en', 'description_fr', 'description_en',
            'hours_min', 'hours_max', 'pricing', 'percent', 'periods', 'billing', 'project_types', 'rate', 'hourly_rates',
        ]);

        try {
            if (filled($entry['id'] ?? null)) {
                if (blank($request->get('summary'))) {
                    return Response::error('Modifier un élément existant devient une révision à valider : ajoute summary (ce qui change et pourquoi).');
                }

                $revision = $revisions->proposeEstimator(['items' => [$entry]], $request->get('summary'), $this->aiClient($request));

                return Response::text("Révision #{$revision->id} proposée ; rien n'a changé sur le site. Le développeur la relit et l'applique ici : ".route('admin.revisions.show', $revision));
            }

            $result = $configurator->apply(['items' => [$entry]]);
        } catch (ValidationException $exception) {
            return Response::error(collect($exception->errors())->flatten()->implode(' '));
        }

        $item = $result['created'][0] ?? $result['updated'][0];

        return Response::text(($result['created'] !== [] ? 'Élément créé, masqué jusqu\'à son activation' : 'Élément mis à jour')
            ." (identifiant {$item['id']}) : ".route('admin.estimator-items.edit', $item['id']));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Élément à modifier (voir get-estimator), par révision à valider ; absent pour en créer un.'),
            'summary' => $schema->string()->description('Avec un id : ce qui change et pourquoi, en une à trois phrases, pour le développeur.'),
            'kind' => $schema->string()->enum(['project_type', 'feature'])->description('project_type : la base d\'un projet complet ; feature : une option qui s\'y ajoute.'),
            'name_fr' => $schema->string()->description('Nom court en français.'),
            'name_en' => $schema->string()->description('Le même en anglais.'),
            'description_fr' => $schema->string()->description('Ce qui est compris, une phrase pour le visiteur.'),
            'description_en' => $schema->string()->description('La même en anglais.'),
            'hours_min' => $schema->integer()->description('Heures de travail réelles au minimum, hors marge (le site l\'ajoute) ; 0 pour une option en pourcentage.'),
            'hours_max' => $schema->integer()->description('Heures de travail réelles au maximum, hors marge.'),
            'pricing' => $schema->string()->enum(['hours', 'percent', 'recurring'])->description('Options : heures fixes, pourcentage des heures du projet, ou recurring (heures par mois facturées à chaque période, hors total du projet : maintenance, hébergement, support…).'),
            'percent' => $schema->integer()->description('Options en pourcentage : ce qui s\'ajoute aux heures du projet, en %.'),
            'periods' => $schema->object([
                'monthly' => $schema->integer(),
                'quarterly' => $schema->integer(),
                'semiannual' => $schema->integer(),
                'yearly' => $schema->integer(),
            ])->description('Options récurrentes : périodes proposées au visiteur, chacune avec sa remise en % (0 sans remise) ; une période absente n\'est pas proposée. Ex. {"monthly": 0, "yearly": 15}. hours_min/hours_max sont alors des heures par mois, sans marge.'),
            'billing' => $schema->string()->enum(['fixed', 'hourly'])->description('Types de projet : fixed (forfait estimé) ou hourly (facturé à l\'heure, pour un périmètre flou ou évolutif).'),
            'project_types' => $schema->array()->items($schema->integer())->description('Options : identifiants des types de projet auxquels l\'option est réservée ; vide pour tous.'),
            'rate' => $schema->integer()->description('Grille tarifaire suivie (voir get-estimator) ; 0 pour n\'en suivre aucune. Pour créer une grille, utilise configure-estimator.'),
            'hourly_rates' => $schema->object([
                'CHF' => $schema->integer(),
                'EUR' => $schema->integer(),
                'USD' => $schema->integer(),
                'XOF' => $schema->integer(),
            ])->description('Taux propres à l\'élément, prioritaires sur sa grille et le taux global ; 0 retire une devise.'),
        ];
    }
}
