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
            'author_name' => ['sometimes', 'string', 'max:255'],
            'author_role' => ['sometimes', 'nullable', 'string', 'max:255'],
            // Champ traduisible : envoyer un tableau remplace toutes les langues, donc fr et en
            // sont exigés ensemble dès qu'on touche au contenu, pour ne jamais en écraser une seule.
            'content' => ['sometimes', 'array'],
            'content.fr' => ['required_with:content', 'string', 'max:2000'],
            'content.en' => ['required_with:content', 'string', 'max:2000'],
            // Accroche et transcription sont facultatives : une langue vide reste vide.
            'highlight' => ['sometimes', 'nullable', 'array'],
            'highlight.fr' => ['nullable', 'string', 'max:280'],
            'highlight.en' => ['nullable', 'string', 'max:280'],
            'video_transcript' => ['sometimes', 'nullable', 'array'],
            'video_transcript.fr' => ['nullable', 'string', 'max:10000'],
            'video_transcript.en' => ['nullable', 'string', 'max:10000'],
            // 95 Mo : Cloudflare refuse les corps de requête de plus de 100 Mo.
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp', 'max:97280'],
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
