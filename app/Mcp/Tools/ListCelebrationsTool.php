<?php

namespace App\Mcp\Tools;

use App\Models\Celebration;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-celebrations')]
#[Description('Liste les surprises du site : bonnes nouvelles qu\'Armi, l\'avatar, annonce au hasard aux visiteurs, qui peuvent féliciter d\'un clic. Pour chacune : message, bouton, raison des félicitations, activation, période, réglages d\'apparition et nombre de félicitations reçues.')]
#[IsReadOnly]
class ListCelebrationsTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json([
            'celebrations' => Celebration::query()->latest()->get()->map(fn (Celebration $celebration): array => [
                'id' => $celebration->id,
                'message' => $celebration->getTranslations('message'),
                'button_label' => $celebration->getTranslations('button_label'),
                'congratulated_for' => $celebration->congratulated_for,
                'is_active' => $celebration->is_active,
                'showing_now' => Celebration::query()->showable()->whereKey($celebration->id)->exists(),
                'starts_at' => $celebration->starts_at?->toDateString(),
                'ends_at' => $celebration->ends_at?->toDateString(),
                'weight' => $celebration->weight,
                'chance_percent' => $celebration->chance_percent,
                'delay_seconds' => $celebration->delay_seconds,
                'display_seconds' => $celebration->display_seconds,
                'snooze_days' => $celebration->snooze_days,
                'congratulations_count' => $celebration->congratulations_count,
                'admin_url' => route('admin.celebrations.edit', $celebration),
            ])->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
