<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EngagementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EngagementRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EngagementStatus::class)],
        ];
    }
}
