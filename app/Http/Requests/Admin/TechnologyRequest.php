<?php

namespace App\Http\Requests\Admin;

use App\Enums\TechnologyCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TechnologyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(TechnologyCategory::class)],
            'icon' => ['nullable', 'string', 'max:255'],
        ];
    }
}
