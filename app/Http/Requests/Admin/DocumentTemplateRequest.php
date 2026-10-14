<?php

namespace App\Http\Requests\Admin;

use App\Models\DocumentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentTemplateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'layout' => ['required', Rule::in(DocumentTemplate::LAYOUTS)],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font' => ['required', Rule::in(array_keys(DocumentTemplate::FONTS))],
            // Vides : le titre par défaut du type (« Devis », « Facture »…), pas de texte.
            'title.fr' => ['nullable', 'string', 'max:60'],
            'title.en' => ['nullable', 'string', 'max:60'],
            'intro.fr' => ['nullable', 'string', 'max:1000'],
            'intro.en' => ['nullable', 'string', 'max:1000'],
            'footer.fr' => ['nullable', 'string', 'max:300'],
            'footer.en' => ['nullable', 'string', 'max:300'],
            'show_logo' => ['boolean'],
            'show_bank_details' => ['boolean'],
            'show_signature' => ['boolean'],
            // PNG ou JPEG : dompdf ne lit ni le SVG ni le WebP de façon fiable.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'remove_logo' => ['boolean'],
            // Aperçu : la langue du document d'exemple.
            'preview_locale' => ['nullable', 'in:fr,en'],
        ];
    }

    /**
     * Les champs du modèle, textes vides retirés.
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $data = $this->safe()->except(['logo', 'remove_logo', 'preview_locale']);

        foreach (['title', 'intro', 'footer'] as $field) {
            $data[$field] = array_filter((array) ($data[$field] ?? []), fn (?string $value): bool => filled($value));
        }

        return $data;
    }

    /** Le logo envoyé, en data URI pour l'aperçu. */
    public function logoDataUri(): ?string
    {
        $file = $this->file('logo');

        return $file === null ? null : 'data:'.$file->getMimeType().';base64,'.base64_encode((string) file_get_contents($file->getRealPath()));
    }
}
