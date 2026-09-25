<?php

namespace App\Models;

use App\Concerns\HasPublicationStatus;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory, HasPublicationStatus, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['name', 'description', 'details'];

    /** @var list<string> */
    protected $fillable = [
        'domain_id',
        'name',
        'description',
        'details',
        'sort_order',
    ];

    /** @return BelongsTo<Domain, $this> */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /** @return BelongsToMany<Technology, $this> */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'skill_technology')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
