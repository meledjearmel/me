<?php

namespace App\Models;

use App\Enums\TestimonialStatus;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['content'];

    /** @var list<string> */
    protected $fillable = [
        'author_name',
        'author_email',
        'author_role',
        'content',
        'project_id',
        'status',
        'is_featured',
        'submitted_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => TestimonialStatus::class,
        'is_featured' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Nombre maximum d'avis « à la une » (et de cartes du paquet de l'accueil). */
    public const int FEATURED_LIMIT = 3;

    /**
     * Les avis du paquet de l'accueil : ceux que j'ai choisis (approuvés et « à la
     * une », trois au plus, même si un seul est choisi). Sans aucun choix, trois
     * avis approuvés tirés au hasard.
     *
     * @return Collection<int, static>
     */
    public static function forHomepage(): Collection
    {
        $approved = static::query()->where('status', TestimonialStatus::Approved);

        $chosen = (clone $approved)
            ->where('is_featured', true)
            ->latest('submitted_at')
            ->limit(self::FEATURED_LIMIT)
            ->get();

        return $chosen->isNotEmpty()
            ? $chosen
            : $approved->inRandomOrder()->limit(self::FEATURED_LIMIT)->get();
    }
}
