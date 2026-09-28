<?php

namespace App\Models;

use Database\Factories\TechnologyCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/** Une catégorie de la stack technique (langages, frameworks, infra…), éditable depuis l'admin. */
class TechnologyCategory extends Model
{
    /** @use HasFactory<TechnologyCategoryFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['label'];

    /** @var list<string> */
    protected $fillable = [
        'key',
        'label',
        'sort_order',
    ];

    /** @return HasMany<Technology, $this> */
    public function technologies(): HasMany
    {
        return $this->hasMany(Technology::class, 'category_id');
    }
}
