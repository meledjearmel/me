<?php

namespace App\Models;

use App\Concerns\HasPublicationStatus;
use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 */
class Education extends Model
{
    /** @use HasFactory<EducationFactory> */
    use HasFactory, HasPublicationStatus, HasTranslations, SoftDeletes;

    /**
     * "Education" est traité comme indénombrable par l'inflecteur Laravel
     * (table "education" au lieu de "educations") : nom de table explicite.
     */
    protected $table = 'educations';

    /** @var list<string> */
    protected $translatable = ['degree', 'field', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'institution',
        'degree',
        'field',
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
}
