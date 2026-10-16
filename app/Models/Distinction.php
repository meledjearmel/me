<?php

namespace App\Models;

use App\Concerns\HasPublicationStatus;
use App\Concerns\PlacesNewAtEnd;
use Database\Factories\DistinctionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Distinction ou prix reçu, affiché dans la carte « Distinction » de la page À propos, chacune
 * avec son compteur de félicitations. Elle peut être rattachée à son contexte : une
 * expérience, une formation ou un projet.
 *
 * @property Carbon $awarded_on
 */
class Distinction extends Model
{
    /** @use HasFactory<DistinctionFactory> */
    use HasFactory, HasPublicationStatus, HasTranslations, PlacesNewAtEnd, SoftDeletes;

    /**
     * Contextes possibles, par clé courte (formulaires, API). La base garde le nom de classe :
     * une table de correspondance globale changerait aussi le type stocké par les médias.
     *
     * @var array<string, class-string<Model>>
     */
    public const array CONTEXTS = [
        'experience' => Experience::class,
        'education' => Education::class,
        'project' => Project::class,
    ];

    /** @var array<int, string> */
    protected $translatable = ['title', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'title',
        'issuer',
        'description',
        'awarded_on',
        'url',
        'distinguishable_type',
        'distinguishable_id',
        'context_type',
        'context_id',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'awarded_on' => 'date:Y-m-d',
        'congratulations_count' => 'integer',
        'sort_order' => 'integer',
    ];

    /** @return MorphTo<Model, $this> */
    public function distinguishable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Clé courte du contexte (`experience`, `education`, `project`), ou null sans contexte. */
    public function contextKey(): ?string
    {
        $type = array_search($this->distinguishable_type, self::CONTEXTS, true);

        return $type === false ? null : $type;
    }

    /**
     * Type du contexte par sa clé courte (formulaires, API, serveur MCP) : la base garde le nom
     * de classe. Vide, la distinction est détachée de son contexte.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function contextType(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->contextKey(),
            set: fn (?string $type): array => isset(self::CONTEXTS[$type ?? ''])
                ? ['distinguishable_type' => self::CONTEXTS[$type]]
                : ['distinguishable_type' => null, 'distinguishable_id' => null],
        );
    }

    /** @return Attribute<int|null, int|string|null> */
    protected function contextId(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->distinguishable_id,
            set: fn (int|string|null $id): array => [
                'distinguishable_id' => $id === null || $id === '' ? null : (int) $id,
            ],
        );
    }
}
