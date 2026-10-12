<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class McpTokenRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Le client qui l'utilise, pour savoir lequel révoquer : « Claude Code », « Codex »…
            'name' => ['required', 'string', 'max:60'],
        ];
    }
}
