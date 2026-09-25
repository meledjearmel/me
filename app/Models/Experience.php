<?php

namespace App\Models;

use App\Concerns\HasPublicationStatus;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 */
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory, HasPublicationStatus, HasTranslations, SoftDeletes;

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

    /** @var array<string, string> */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /** @return HasMany<ExperienceHighlight, $this> */
    public function highlights(): HasMany
    {
        return $this->hasMany(ExperienceHighlight::class);
    }
}
