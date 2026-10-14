<?php

namespace App\Mcp\Tools;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('moderate-testimonial')]
#[Description('Publie (approved) ou refuse (rejected) un avis client (voir get-inbox), et peut le mettre en avant sur le site. Un avis approuvé s\'affiche aussitôt en public : ne l\'appelle que sur demande explicite du développeur pour cet avis.')]
class ModerateTestimonialTool extends Tool
{
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'status' => ['required', Rule::in([TestimonialStatus::Approved->value, TestimonialStatus::Rejected->value])],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $testimonial = Testimonial::query()->find($data['id']);

        if ($testimonial === null) {
            return Response::error('Avis introuvable.');
        }

        $testimonial->update(array_filter([
            'status' => $data['status'],
            'is_featured' => $data['status'] === TestimonialStatus::Approved->value ? ($data['is_featured'] ?? null) : false,
        ], fn (mixed $value): bool => $value !== null));

        return Response::text(($data['status'] === TestimonialStatus::Approved->value ? 'Avis publié' : 'Avis refusé')
            ." ({$testimonial->author_name}). ".route('admin.testimonials.show', $testimonial));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('L\'avis (testimonials de get-inbox).')->required(),
            'status' => $schema->string()->enum(['approved', 'rejected'])->required(),
            'is_featured' => $schema->boolean()->description('Avis approuvé : le mettre en avant sur le site.'),
        ];
    }
}
