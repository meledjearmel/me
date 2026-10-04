<?php

namespace App\Http\Requests\Admin;

use App\Enums\UsesCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsesItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(UsesCategory::class)],
            'name' => ['required', 'string', 'max:120'],
            'description.fr' => ['nullable', 'string', 'max:300'],
            'description.en' => ['nullable', 'string', 'max:300'],
            'url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
