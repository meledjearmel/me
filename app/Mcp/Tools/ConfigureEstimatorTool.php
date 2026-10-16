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

#[Name('configure-estimator')]
#[Description('Configure tout l\'estimateur de projet en un seul appel : réglages (taux horaire global par devise, marge, heures par semaine), grilles tarifaires (taux nommés, avec une ref) et autant d\'éléments que nécessaire, créés ou modifiés (avec id). Chaque élément peut porter une ref, pour qu\'une option la vise dans project_types : l\'option est alors réservée à ces types de projet (identifiants existants ou ref de types créés dans le même appel). Une option se chiffre en heures ou en pourcentage des heures du projet. Le taux d\'un élément : ses hourly_rates, sinon sa grille (rate), sinon le taux global. Tout est validé puis appliqué d\'un bloc ; les éléments créés restent masqués jusqu\'à leur activation dans l\'administration. Lis d\'abord get-estimator.')]
class ConfigureEstimatorTool extends Tool
{
    public function handle(Request $request, EstimatorConfigurator $configurator): Response
    {
        try {
            $result = $configurator->apply($request->only(['settings', 'rates', 'items']));
        } catch (ValidationException $exception) {
            return Response::error('Configuration refusée, rien n\'a été enregistré : '.collect($exception->errors())
                ->map(fn (array $messages, string $field): string => "{$field} : {$messages[0]}")
                ->implode(' ; '));
        }

        return Response::json([
            ...$result,
            'admin_url' => route('admin.estimator-items.index'),
            'note' => 'Les éléments créés sont masqués : le développeur les active dans l\'administration après relecture.',
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'settings' => $schema->object([
                'hourly_rates' => $schema->object([
                    'CHF' => $schema->integer(),
                    'EUR' => $schema->integer(),
                    'USD' => $schema->integer(),
                    'XOF' => $schema->integer(),
                ])->description('Taux horaire par devise ; 0 retire la devise.'),
                'risk_percent' => $schema->integer()->description('Marge pour imprévus ajoutée aux heures, en %.'),
                'hours_per_week' => $schema->integer()->description('Heures facturables par semaine, pour le délai.'),
            ])->description('Seulement si le développeur demande de changer les réglages.'),
            'rates' => $schema->array()->items($schema->object([
                'id' => $schema->integer()->description('Grille existante à modifier (voir get-estimator).'),
                'ref' => $schema->string()->description('Identifiant court et unique dans l\'appel, que les éléments citent dans rate.'),
                'name_fr' => $schema->string()->description('Nom de la grille, ex. « Développement avancé ».'),
                'name_en' => $schema->string(),
                'hourly_rates' => $schema->object([
                    'CHF' => $schema->integer(),
                    'EUR' => $schema->integer(),
                    'USD' => $schema->integer(),
                    'XOF' => $schema->integer(),
                ])->description('Taux horaire par devise ; une devise absente ou à 0 suit le taux global.'),
            ]))->description('Grilles tarifaires à créer ou modifier : des taux nommés partagés par plusieurs éléments.'),
            'items' => $schema->array()->items($schema->object([
                'id' => $schema->integer()->description('Élément existant à modifier (seuls les champs envoyés changent).'),
                'ref' => $schema->string()->description('Identifiant court et unique dans l\'appel, ex. « app-mobile ».'),
                'kind' => $schema->string()->enum(['project_type', 'feature']),
                'name_fr' => $schema->string(),
                'name_en' => $schema->string(),
                'description_fr' => $schema->string(),
                'description_en' => $schema->string(),
                'hours_min' => $schema->integer()->description('Heures réelles hors marge (0 pour une option en pourcentage).'),
                'hours_max' => $schema->integer(),
                'pricing' => $schema->string()->enum(['hours', 'percent', 'recurring'])->description('Options : heures fixes ; pourcentage des heures du projet (coût qui grandit avec le projet, ex. multilingue) ; ou recurring (heures par mois facturées à chaque période, hors total du projet : maintenance, hébergement, support…).'),
                'percent' => $schema->integer()->description('Options en pourcentage : ce qui s\'ajoute aux heures du projet, en %.'),
                'periods' => $schema->object([
                    'monthly' => $schema->integer(),
                    'quarterly' => $schema->integer(),
                    'semiannual' => $schema->integer(),
                    'yearly' => $schema->integer(),
                ])->description('Options récurrentes : périodes proposées au visiteur, chacune avec sa remise en % (0 sans remise) ; une période absente n\'est pas proposée. Ex. {"monthly": 0, "yearly": 15}. hours_min/hours_max sont alors des heures par mois, sans marge.'),
                'billing' => $schema->string()->enum(['fixed', 'hourly'])->description('Types de projet : forfait, ou à l\'heure si le périmètre est flou.'),
                'project_types' => $schema->array()->items($schema->string())->description('Options : ref ou identifiant des types de projet auxquels l\'option est réservée ; vide pour tous.'),
                'rate' => $schema->string()->description('Grille tarifaire suivie : identifiant existant, ref d\'une grille de rates, ou « 0 » pour n\'en suivre aucune.'),
                'hourly_rates' => $schema->object([
                    'CHF' => $schema->integer(),
                    'EUR' => $schema->integer(),
                    'USD' => $schema->integer(),
                    'XOF' => $schema->integer(),
                ])->description('Taux propres à l\'élément, prioritaires sur sa grille et le taux global ; 0 retire une devise. Ignorés pour une option en pourcentage (taux du type de projet).'),
            ]))->description('Éléments à créer ou modifier.'),
        ];
    }
}
