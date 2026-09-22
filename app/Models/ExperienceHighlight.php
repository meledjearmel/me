<?php

namespace App\Models;

use Database\Factories\ExperienceHighlightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class ExperienceHighlight extends Model
{
    /** @use HasFactory<ExperienceHighlightFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['text'];

    /** @var array<int, string> */
    protected $fillable = [
        'experience_id',
        'text',
        'sort_order',
    ];

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }
}
