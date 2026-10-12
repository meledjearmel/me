<?php

namespace App\Mcp\Tools;

use App\Models\Technology;
use App\Services\TechnologyIconLibrary;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Ajoute au site une technologie absente de get-catalog (même nom refusé), pour pouvoir la cocher sur un projet. Son logo est importé depuis Iconify s\'il est donné (ex. « logos:laravel », collections logos, devicon ou simple-icons), sinon il reste à choisir dans l\'administration.')]
class CreateTechnologyTool extends Tool
{
    public function handle(Request $request, TechnologyIconLibrary $icons): Response
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', Rule::exists('technology_categories', 'id')->whereNull('deleted_at')],
            'description_fr' => ['required', 'string', 'max:150'],
            'description_en' => ['required', 'string', 'max:150'],
            'iconify_id' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = Technology::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->first();

        if ($existing !== null) {
            return Response::error("La technologie existe déjà (identifiant {$existing->id}).");
        }

        $slug = Str::slug($data['name']);
        $icon = match (true) {
            $icons->exists($slug) => $slug,
            filled($data['iconify_id'] ?? null) && $icons->import($data['iconify_id'], $slug) => $slug,
            default => null,
        };

        $technology = Technology::query()->create([
            'name' => $data['name'],
            'category_id' => $data['category_id'],
            'description' => ['fr' => $data['description_fr'], 'en' => $data['description_en']],
            'icon' => $icon,
        ]);

        $message = "Technologie « {$technology->name} » créée (identifiant {$technology->id}).";

        if ($icon === null) {
            $message .= ' Logo introuvable : à choisir dans l\'administration ('.route('admin.technologies.edit', $technology).').';
        }

        return Response::text($message);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nom officiel, ex. « Laravel Reverb ».')->required(),
            'category_id' => $schema->integer()->description('Catégorie (technology_categories de get-catalog).')->required(),
            'description_fr' => $schema->string()->description('Une phrase en français (150 caractères max.) : ce que c\'est et à quoi elle sert dans mes projets. Affichée au survol du logo.')->required(),
            'description_en' => $schema->string()->description('La même phrase en anglais (150 max.).')->required(),
            'iconify_id' => $schema->string()->description('Logo Iconify, ex. « logos:laravel » ou « simple-icons:livewire ».'),
        ];
    }
}
