<?php

namespace App\Http\Requests\Admin;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'is_featured' => ['boolean'],
        ];
    }

    /**
     * Trois avis « à la une » au maximum : on n'en ajoute pas un quatrième.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $others = Testimonial::query()
                    ->where('is_featured', true)
                    ->whereKeyNot($this->route('testimonial')->getKey())
                    ->count();

                if ($this->boolean('is_featured') && $others >= Testimonial::FEATURED_LIMIT) {
                    $validator->errors()->add('is_featured', 'Trois avis au maximum peuvent être à la une : retirez-en un d\'abord.');
                }
            },
        ];
    }
}
