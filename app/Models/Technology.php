<?php

namespace App\Models;

use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['description'];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'category_id',
        'icon',
        'description',
    ];

    /** @return BelongsTo<TechnologyCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TechnologyCategory::class);
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_technology');
    }
}
