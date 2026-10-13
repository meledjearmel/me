<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** La description libre d'un projet, pour que l'IA présélectionne le type et les options de l'estimateur. */
class SuggestEstimateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'min:15', 'max:2000'],
        ];
    }
}
