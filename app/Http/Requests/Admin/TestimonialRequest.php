<?php

namespace App\Http\Requests\Admin;

use App\Enums\TestimonialStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestimonialRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TestimonialStatus::class)],
            'project_id' => ['nullable', 'exists:projects,id'],
        ];
    }
}
