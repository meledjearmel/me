<?php

namespace App\Models;

use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['role', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'company',
        'role',
        'location',
        'start_date',
        'end_date',
        'description',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /** @return HasMany<ExperienceHighlight, $this> */
    public function highlights(): HasMany
    {
        return $this->hasMany(ExperienceHighlight::class);
    }
}
