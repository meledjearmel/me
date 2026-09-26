<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:'.config('ai.chat.max_message_length')],
            'history' => ['nullable', 'array', 'max:'.config('ai.chat.max_history')],
            'history.*.role' => ['required', 'string', 'in:user,assistant'],
            // Les réponses de l'assistant sont plus longues que les questions.
            'history.*.content' => ['required', 'string', 'max:4000'],
        ];
    }

    /**
     * Historique de la conversation, tel que le navigateur le renvoie.
     *
     * @return list<array{role: string, content: string}>
     */
    public function history(): array
    {
        return array_values($this->validated('history') ?? []);
    }
}
