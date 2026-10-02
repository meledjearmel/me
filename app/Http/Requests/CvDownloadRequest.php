<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CvDownloadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Facultatif : le visiteur peut laisser son email pour être recontacté.
            'email' => ['nullable', 'email', 'max:255'],
            // Champ piège invisible : rempli, c'est un robot.
            'website' => ['prohibited'],
        ];
    }
}
