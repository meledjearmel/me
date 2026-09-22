<?php

namespace App\Models;

use App\Enums\TechnologyCategory;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'category',
        'icon',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => TechnologyCategory::class,
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_technology');
    }
}
