<?php

namespace App\Http\Requests\Ai;

use App\Enums\EstimatorItemKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Ce que l'IA doit chiffrer pour la grille de l'estimateur : la nature, un nom, des précisions. */
class SuggestEstimatorItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(EstimatorItemKind::class)],
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
