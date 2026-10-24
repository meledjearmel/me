<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProposalRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', Rule::in(Proposal::PLATFORMS)],
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'job_title' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'job_url' => ['nullable', 'url:http,https', 'max:2000'],
            'job_posting' => ['nullable', 'string', 'max:20000'],
            'body' => ['required', 'string', 'max:10000'],
            'screening_answers' => ['nullable', 'string', 'max:10000'],
            'bid' => ['nullable', 'string', 'max:120'],
            'connects' => ['nullable', 'integer', 'min:0', 'max:500'],
            'status' => ['required', Rule::enum(ProposalStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
