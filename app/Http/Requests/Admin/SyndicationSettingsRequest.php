<?php

namespace App\Http\Requests\Admin;

use App\Enums\SyndicationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyndicationSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (['devto', 'hashnode'] as $platform) {
            $rules["{$platform}_mode"] = ['required', Rule::enum(SyndicationMode::class)];
            // Vide : la clé enregistrée est gardée.
            $rules["{$platform}_api_key"] = ['nullable', 'string', 'max:500'];
            $rules["{$platform}_clear_key"] = ['boolean'];
            $rules["{$platform}_locale"] = ['required', Rule::in(['fr', 'en'])];
        }

        return [
            ...$rules,
            'hashnode_publication_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
