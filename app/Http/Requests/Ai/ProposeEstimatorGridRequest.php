<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

/** Ce que le développeur décrit de son activité, pour que l'IA propose toute la grille de l'estimateur. */
class ProposeEstimatorGridRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brief' => ['required', 'string', 'min:20', 'max:5000'],
        ];
    }
}
