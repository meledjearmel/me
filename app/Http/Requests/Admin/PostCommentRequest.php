<?php

namespace App\Http\Requests\Admin;

use App\Enums\CommentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostCommentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Modération : `approved` publie le commentaire, `rejected` le masque.
            'status' => ['required', Rule::enum(CommentStatus::class)],
        ];
    }
}
