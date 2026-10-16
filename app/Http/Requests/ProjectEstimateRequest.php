<?php

namespace App\Http\Requests;

use App\Enums\EstimatorItemKind;
use App\Enums\EstimatorPeriod;
use App\Models\EstimatorItem;
use App\Services\ProjectEstimator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

/** Les choix faits dans l'estimateur de projet, et les coordonnées quand le visiteur envoie sa demande. */
class ProjectEstimateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'project_type_id' => ['required', 'integer', $this->activeItem(EstimatorItemKind::ProjectType)],
            'feature_ids' => ['nullable', 'array', 'max:50'],
            'feature_ids.*' => ['integer', 'distinct', $this->activeItem(EstimatorItemKind::Feature)],
            // Options récurrentes : la période choisie par option (id => période) ; à défaut, la première proposée.
            'periods' => ['nullable', 'array', 'max:50'],
            'periods.*' => [Rule::enum(EstimatorPeriod::class)],
            // Seules les devises qui ont un taux horaire sont proposées.
            'currency' => ['required', Rule::in(app(ProjectEstimator::class)->currencies())],
        ];

        // La fourchette en cours de saisie (GET) n'a besoin que des choix.
        if ($this->isMethod('GET')) {
            return $rules;
        }

        return [
            ...$rules,
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'timeline' => ['nullable', Rule::in(EngagementRequest::TIMELINES)],
            'message' => ['nullable', 'string', 'max:5000'],
            // Piège anti-spam : un humain ne le voit ni ne le remplit jamais.
            'website' => ['prohibited'],
        ];
    }

    /**
     * Une option réservée à d'autres types de projet n'est pas acceptée.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $projectTypeId = (int) $this->input('project_type_id');
                $refused = EstimatorItem::query()
                    ->with('projectTypes')
                    ->whereIn('id', (array) $this->input('feature_ids', []))
                    ->get()
                    ->reject(fn (EstimatorItem $feature): bool => $feature->isAvailableFor($projectTypeId));

                if ($refused->isNotEmpty()) {
                    $validator->errors()->add('feature_ids', __('Une option choisie ne s\'applique pas à ce type de projet.'));
                }
            },
        ];
    }

    private function activeItem(EstimatorItemKind $kind): Exists
    {
        return Rule::exists('estimator_items', 'id')
            ->where('kind', $kind->value)
            ->where('is_active', true)
            ->whereNull('deleted_at');
    }
}
