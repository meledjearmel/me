<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Track
 */
class TrackResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'music_genre_id' => $this->music_genre_id,
            'title' => $this->title,
            'artist' => $this->artist,
            'sort_order' => $this->sort_order,
            'audio_url' => $this->audioUrl(),
        ];
    }
}
