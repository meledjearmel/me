<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    /**
     * L'anglais est facultatif : sans traduction, la version anglaise du site affiche le français.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title.fr' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            // « feed » est pris par le flux RSS (/blog/feed).
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'not_in:feed', Rule::unique('posts', 'slug')->ignore($this->route('post'))],
            'excerpt.fr' => ['nullable', 'string', 'max:300'],
            'excerpt.en' => ['nullable', 'string', 'max:300'],
            'body.fr' => ['required', 'string'],
            'body.en' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'tags' => ['array', 'max:8'],
            'tags.*' => ['string', 'max:40'],
        ];
    }
}
