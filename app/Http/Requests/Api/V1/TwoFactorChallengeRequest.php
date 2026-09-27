<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TwoFactorChallengeRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'challenge' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ];
    }

    /**
     * Il faut un code TOTP ou un code de secours, jamais aucun des deux.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (blank($this->input('code')) && blank($this->input('recovery_code'))) {
                    $validator->errors()->add('code', __('Le code de vérification ou un code de secours est requis.'));
                }
            },
        ];
    }
}
