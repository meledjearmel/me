<?php

namespace App\Mcp\Tools;

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
#[Description('Crée un élément de la grille de l\'estimateur (type de projet ou option), ou en modifie un avec son id (seuls les champs envoyés changent). Pour plusieurs éléments ou les réglages, préfère configure-estimator. Un élément créé reste masqué aux visiteurs tant que le développeur ne l\'a pas activé dans l\'administration.')]
class SaveEstimatorItemTool extends Tool
{
    public function handle(Request $request, EstimatorConfigurator $configurator): Response
    {
        $entry = $request->only([
            'id', 'kind', 'name_fr', 'name_en', 'description_fr', 'description_en',
            'hours_min', 'hours_max', 'pricing', 'percent', 'billing', 'project_types',
        ]);

        try {
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
            'id' => $schema->integer()->description('Élément à modifier (voir get-estimator) ; absent pour en créer un.'),
            'kind' => $schema->string()->enum(['project_type', 'feature'])->description('project_type : la base d\'un projet complet ; feature : une option qui s\'y ajoute.'),
            'name_fr' => $schema->string()->description('Nom court en français.'),
            'name_en' => $schema->string()->description('Le même en anglais.'),
            'description_fr' => $schema->string()->description('Ce qui est compris, une phrase pour le visiteur.'),
            'description_en' => $schema->string()->description('La même en anglais.'),
            'hours_min' => $schema->integer()->description('Heures de travail réelles au minimum, hors marge (le site l\'ajoute) ; 0 pour une option en pourcentage.'),
            'hours_max' => $schema->integer()->description('Heures de travail réelles au maximum, hors marge.'),
            'pricing' => $schema->string()->enum(['hours', 'percent'])->description('Options : heures fixes, ou pourcentage des heures du projet.'),
            'percent' => $schema->integer()->description('Options en pourcentage : ce qui s\'ajoute aux heures du projet, en %.'),
            'billing' => $schema->string()->enum(['fixed', 'hourly'])->description('Types de projet : fixed (forfait estimé) ou hourly (facturé à l\'heure, pour un périmètre flou ou évolutif).'),
            'project_types' => $schema->array()->items($schema->integer())->description('Options : identifiants des types de projet auxquels l\'option est réservée ; vide pour tous.'),
        ];
    }
}
