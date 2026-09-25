<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Engagement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Engagement
 */
class EngagementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'subject' => $this->subject,
            'job_profile' => $this->whenLoaded('jobProfile', fn (): ?array => $this->jobProfile === null ? null : [
                'id' => $this->jobProfile->id,
                'label' => $this->jobProfile->getTranslations('label'),
            ]),
            'contract' => $this->contract,
            'budget' => $this->budget_label,
            'timeline' => $this->timeline,
            'message' => $this->message,
            'locale' => $this->locale,
            'cv_sent_at' => $this->cv_sent_at,
            'created_at' => $this->created_at,
        ];
    }
}
