<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PostSeriesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name.fr' => ['required', 'string', 'max:80'],
            'name.en' => ['required', 'string', 'max:80'],
        ];
    }
}
