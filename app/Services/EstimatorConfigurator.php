<?php

namespace App\Services;

use App\Enums\EstimatorBilling;
use App\Enums\EstimatorItemKind;
use App\Enums\EstimatorPricing;
use App\Models\EstimatorItem;
use App\Models\EstimatorRate;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Configure l'estimateur en une fois, pour le serveur MCP et la proposition de l'IA de
 * l'administration : réglages (taux, marge, rythme) et éléments à créer ou modifier. Une
 * option peut viser des types de projet existants (identifiant) ou créés dans le même
 * envoi (leur « ref »). Les grilles tarifaires se créent dans le même envoi, et un élément
 * peut suivre une grille (identifiant ou ref) et porter ses propres taux. Tout est validé
 * avant d'écrire, et appliqué d'un bloc.
 */
class EstimatorConfigurator
{
    /**
     * @param  array<string, mixed>  $input  {settings?: {...}, rates?: list<{...}>, items?: list<{...}>}
     * @return array{settings_updated: bool, rates: list<array{id: int, name: string}>, created: list<array{id: int, name: string}>, updated: list<array{id: int, name: string}>}
     *
     * @throws ValidationException
     */
    public function apply(array $input, bool $activateNew = false): array
    {
        $data = Validator::make($input, self::rules())->validate();
        $refs = collect($data['items'] ?? [])->pluck('ref')->filter()->all();

        // Un identifiant écrit en texte (« 12 ») qui n'est pas une ref devient un identifiant.
        $items = collect($data['items'] ?? [])->map(function (array $entry) use ($refs): array {
            if (isset($entry['project_types'])) {
                $entry['project_types'] = array_map(
                    fn (mixed $target): int|string => is_string($target) && ctype_digit($target) && ! in_array($target, $refs, true) ? (int) $target : $target,
                    $entry['project_types'],
                );
            }

            return $entry;
        })->all();

        $this->checkReferences($items, $refs, $data['rates'] ?? []);

        return DB::transaction(function () use ($data, $items, $activateNew): array {
            $settingsUpdated = $this->applySettings($data['settings'] ?? null);
            $rates = $this->applyRates($data['rates'] ?? []);
            $saved = [];
            $created = [];
            $updated = [];

            // D'abord les éléments eux-mêmes, pour que les options puissent viser les types créés.
            foreach ($items as $index => $entry) {
                $item = isset($entry['id']) ? EstimatorItem::query()->findOrFail($entry['id']) : new EstimatorItem(['is_active' => $activateNew]);
                $isNew = ! $item->exists;

                $this->fill($item, $entry);

                if (array_key_exists('rate', $entry)) {
                    $item->estimator_rate_id = match (true) {
                        is_string($entry['rate']) && isset($rates['refs'][$entry['rate']]) => $rates['refs'][$entry['rate']],
                        (int) $entry['rate'] > 0 => (int) $entry['rate'],
                        default => null,
                    };
                }

                if (array_key_exists('hourly_rates', $entry)) {
                    // Les devises envoyées remplacent les siennes ; 0 en retire une.
                    $item->hourly_rates = [...EstimatorItem::filterRates($item->hourly_rates), ...($entry['hourly_rates'] ?? [])];
                }

                // Une modification partielle ne doit pas inverser la fourchette déjà enregistrée.
                if ($item->hours_max < $item->hours_min) {
                    throw ValidationException::withMessages([
                        "items.{$index}.hours_max" => 'hours_max doit être supérieur ou égal à hours_min ('.$item->hours_min.').',
                    ]);
                }

                $item->save();
                $saved[$index] = $item;

                if (isset($entry['ref'])) {
                    $saved[$entry['ref']] = $item;
                }

                $summary = ['id' => $item->id, 'name' => $item->getTranslation('name', 'fr')];
                $isNew ? $created[] = $summary : $updated[] = $summary;
            }

            foreach ($items as $index => $entry) {
                if (! array_key_exists('project_types', $entry) || $saved[$index]->kind !== EstimatorItemKind::Feature) {
                    continue;
                }

                $saved[$index]->projectTypes()->sync(collect($entry['project_types'])
                    ->map(fn (int|string $target): int => is_int($target) ? $target : $saved[$target]->id)
                    ->unique()
                    ->values()
                    ->all());
            }

            return ['settings_updated' => $settingsUpdated, 'rates' => $rates['summary'], 'created' => $created, 'updated' => $updated];
        });
    }

    /**
     * Crée ou modifie les grilles tarifaires envoyées.
     *
     * @param  list<array<string, mixed>>  $entries
     * @return array{refs: array<string, int>, summary: list<array{id: int, name: string}>}
     */
    private function applyRates(array $entries): array
    {
        $refs = [];
        $summary = [];

        foreach ($entries as $entry) {
            $rate = isset($entry['id']) ? EstimatorRate::query()->findOrFail($entry['id']) : new EstimatorRate(['hourly_rates' => []]);

            foreach (['fr', 'en'] as $locale) {
                if (isset($entry["name_{$locale}"])) {
                    $rate->setTranslation('name', $locale, $entry["name_{$locale}"]);
                }
            }

            if (array_key_exists('hourly_rates', $entry)) {
                $rate->hourly_rates = EstimatorItem::filterRates([...$rate->rates(), ...($entry['hourly_rates'] ?? [])]);
            }

            $rate->save();

            if (isset($entry['ref'])) {
                $refs[$entry['ref']] = $rate->id;
            }

            $summary[] = ['id' => $rate->id, 'name' => $rate->getTranslation('name', 'fr')];
        }

        return ['refs' => $refs, 'summary' => $summary];
    }

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'settings' => ['nullable', 'array'],
            'settings.hourly_rates' => ['nullable', 'array:'.implode(',', EstimatorItem::CURRENCIES)],
            'settings.hourly_rates.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'settings.risk_percent' => ['nullable', 'integer', 'min:0', 'max:200'],
            'settings.hours_per_week' => ['nullable', 'integer', 'min:1', 'max:80'],
            'rates' => ['nullable', 'array', 'max:20'],
            'rates.*.id' => ['nullable', 'integer', Rule::exists('estimator_rates', 'id')],
            'rates.*.ref' => ['nullable', 'string', 'max:60', 'distinct'],
            'rates.*.name_fr' => ['required_without:rates.*.id', 'string', 'max:255'],
            'rates.*.name_en' => ['required_without:rates.*.id', 'string', 'max:255'],
            'rates.*.hourly_rates' => ['nullable', 'array:'.implode(',', EstimatorItem::CURRENCIES)],
            'rates.*.hourly_rates.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'items' => ['nullable', 'array', 'max:100'],
            'items.*.id' => ['nullable', 'integer', Rule::exists('estimator_items', 'id')->whereNull('deleted_at')],
            'items.*.ref' => ['nullable', 'string', 'max:60', 'distinct'],
            'items.*.kind' => ['required_without:items.*.id', Rule::enum(EstimatorItemKind::class)],
            'items.*.name_fr' => ['required_without:items.*.id', 'string', 'max:255'],
            'items.*.name_en' => ['required_without:items.*.id', 'string', 'max:255'],
            'items.*.description_fr' => ['nullable', 'string', 'max:1000'],
            'items.*.description_en' => ['nullable', 'string', 'max:1000'],
            'items.*.hours_min' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'items.*.hours_max' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'items.*.pricing' => ['nullable', Rule::enum(EstimatorPricing::class)],
            'items.*.percent' => ['nullable', 'integer', 'min:1', 'max:500'],
            'items.*.billing' => ['nullable', Rule::enum(EstimatorBilling::class)],
            'items.*.project_types' => ['nullable', 'array'],
            'items.*.project_types.*' => ['required'],
            // Identifiant d'une grille existante, ref d'une grille créée dans l'envoi, ou 0 pour n'en suivre aucune.
            'items.*.rate' => ['nullable'],
            'items.*.hourly_rates' => ['nullable', 'array:'.implode(',', EstimatorItem::CURRENCIES)],
            'items.*.hourly_rates.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Les cibles des options (identifiant d'un type de projet existant, ou « ref » d'un type
     * créé dans le même envoi) et la cohérence des heures, vérifiées avant toute écriture.
     *
     * @param  list<array<string, mixed>>  $items
     * @param  list<string>  $refs
     * @param  list<array<string, mixed>>  $rates
     *
     * @throws ValidationException
     */
    private function checkReferences(array $items, array $refs, array $rates): void
    {
        $errors = [];
        $rateRefs = collect($rates)->pluck('ref')->filter()->all();
        $existingRates = EstimatorRate::query()->pluck('id')->all();

        foreach ($items as $index => $entry) {
            $rate = $entry['rate'] ?? null;
            $valid = $rate === null
                || in_array($rate, $rateRefs, true)
                || (is_numeric($rate) && ((int) $rate === 0 || in_array((int) $rate, $existingRates, true)));

            if (! $valid) {
                $errors["items.{$index}.rate"] = "« {$rate} » n'est ni une grille tarifaire existante, ni la ref d'une grille créée dans cet envoi.";
            }
        }
        $projectTypeRefs = collect($items)
            ->filter(fn (array $entry): bool => ($entry['kind'] ?? null) === EstimatorItemKind::ProjectType->value && isset($entry['ref']))
            ->pluck('ref')
            ->all();
        $existingProjectTypes = EstimatorItem::query()->where('kind', EstimatorItemKind::ProjectType)->pluck('id')->all();

        foreach ($items as $index => $entry) {
            foreach ($entry['project_types'] ?? [] as $position => $target) {
                $valid = is_int($target) ? in_array($target, $existingProjectTypes, true) : in_array($target, $projectTypeRefs, true);

                if (! $valid) {
                    $errors["items.{$index}.project_types.{$position}"] = "« {$target} » n'est ni un type de projet existant, ni la ref d'un type créé dans cet envoi.";
                }
            }

            $isPercent = ($entry['pricing'] ?? null) === EstimatorPricing::Percent->value;

            if ($isPercent && ! isset($entry['percent']) && ! isset($entry['id'])) {
                $errors["items.{$index}.percent"] = 'Une option en pourcentage a besoin de percent.';
            }

            if (! $isPercent && ! isset($entry['id']) && (! isset($entry['hours_min'], $entry['hours_max']))) {
                $errors["items.{$index}.hours_min"] = 'Un élément en heures a besoin de hours_min et hours_max.';
            }

            if (isset($entry['hours_min'], $entry['hours_max']) && $entry['hours_max'] < $entry['hours_min']) {
                $errors["items.{$index}.hours_max"] = 'hours_max doit être supérieur ou égal à hours_min.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @param  array<string, mixed>|null  $settings */
    private function applySettings(?array $settings): bool
    {
        if ($settings === null || $settings === []) {
            return false;
        }

        $current = SiteSetting::current();
        $changes = [];

        if (array_key_exists('hourly_rates', $settings)) {
            $changes['estimator_hourly_rates'] = array_filter(
                [...$current->estimatorRates(), ...($settings['hourly_rates'] ?? [])],
                fn (mixed $rate): bool => (int) $rate > 0,
            );
        }

        foreach (['risk_percent' => 'estimator_risk_percent', 'hours_per_week' => 'estimator_hours_per_week'] as $key => $column) {
            if (isset($settings[$key])) {
                $changes[$column] = $settings[$key];
            }
        }

        $current->update($changes);

        return $changes !== [];
    }

    /** @param  array<string, mixed>  $entry */
    private function fill(EstimatorItem $item, array $entry): void
    {
        foreach (['kind', 'hours_min', 'hours_max', 'pricing', 'percent', 'billing', 'is_active'] as $field) {
            if (array_key_exists($field, $entry) && $entry[$field] !== null) {
                $item->{$field} = $entry[$field];
            }
        }

        foreach (['name', 'description'] as $field) {
            foreach (['fr', 'en'] as $locale) {
                if (array_key_exists("{$field}_{$locale}", $entry)) {
                    $item->setTranslation($field, $locale, $entry["{$field}_{$locale}"]);
                }
            }
        }
    }
}
