<?php

namespace App\Mcp\Tools;

use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Models\EstimatorItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('save-estimator-item')]
#[Description('Crée un élément de la grille de l\'estimateur (type de projet ou option), ou en modifie un avec son id (seuls les champs envoyés changent). Un élément créé reste masqué aux visiteurs tant que le développeur ne l\'a pas activé dans l\'administration.')]
class SaveEstimatorItemTool extends Tool
{
    public function handle(Request $request): Response
    {
        $creating = $request->get('id') === null;
        $required = $creating ? 'required' : 'sometimes';

        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'kind' => [$required, Rule::enum(EstimatorItemKind::class)],
            'name_fr' => [$required, 'string', 'max:255'],
            'name_en' => [$required, 'string', 'max:255'],
            'description_fr' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'hours_min' => [$required, 'integer', 'min:0', 'max:5000'],
            'hours_max' => [$required, 'integer', 'min:0', 'max:5000'],
            'billing' => ['nullable', Rule::enum(EstimatorBilling::class)],
        ]);

        $item = $creating ? new EstimatorItem(['is_active' => false]) : EstimatorItem::query()->find($data['id']);

        if ($item === null) {
            return Response::error('Élément de l\'estimateur introuvable.');
        }

        foreach (['kind', 'hours_min', 'hours_max', 'billing'] as $field) {
            if (array_key_exists($field, $data)) {
                $item->{$field} = $data[$field];
            }
        }

        foreach (['name', 'description'] as $field) {
            foreach (['fr', 'en'] as $locale) {
                if (array_key_exists("{$field}_{$locale}", $data)) {
                    $item->setTranslation($field, $locale, $data["{$field}_{$locale}"]);
                }
            }
        }

        if ($item->hours_max < $item->hours_min) {
            return Response::error('hours_max doit être supérieur ou égal à hours_min.');
        }

        $item->save();

        return Response::text(($creating ? 'Élément créé, masqué jusqu\'à son activation' : 'Élément mis à jour')
            ." (identifiant {$item->id}) : ".route('admin.estimator-items.edit', $item));
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
            'hours_min' => $schema->integer()->description('Heures de travail réelles au minimum, hors marge (le site l\'ajoute).'),
            'hours_max' => $schema->integer()->description('Heures de travail réelles au maximum, hors marge.'),
            'billing' => $schema->string()->enum(['fixed', 'hourly'])->description('Types de projet : fixed (forfait estimé) ou hourly (facturé à l\'heure, pour un périmètre flou ou évolutif).'),
        ];
    }
}
