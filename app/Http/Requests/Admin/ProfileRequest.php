<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
            'cv_last_name' => ['nullable', 'string', 'max:255'],
            'cv_first_name' => ['nullable', 'string', 'max:255'],
            'headline.fr' => ['required', 'string', 'max:255'],
            'headline.en' => ['required', 'string', 'max:255'],
            'bio_short.fr' => ['required', 'string'],
            'bio_short.en' => ['required', 'string'],
            'bio_full.fr' => ['required', 'string'],
            'bio_full.en' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'social_links.github' => ['nullable', 'url', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'cv_photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
