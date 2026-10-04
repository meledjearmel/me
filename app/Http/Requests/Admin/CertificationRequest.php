<?php

namespace App\Http\Requests\Admin;

use App\Enums\CertificationKind;
use App\Enums\PublicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CertificationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(CertificationKind::class)],
            'name.fr' => ['required', 'string', 'max:160'],
            'name.en' => ['required', 'string', 'max:160'],
            'issuer' => ['required', 'string', 'max:120'],
            'issued_on' => ['required', 'date', 'before_or_equal:today'],
            'expires_on' => ['nullable', 'date', 'after:issued_on'],
            'credential_id' => ['nullable', 'string', 'max:120'],
            'credential_url' => ['nullable', 'url', 'max:255'],
            'status' => ['sometimes', Rule::enum(PublicationStatus::class)],
            'sort_order' => ['integer', 'min:0'],
            'badge' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
