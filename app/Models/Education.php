<?php

namespace App\Models;

use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Education extends Model
{
    /** @use HasFactory<EducationFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
