<?php

namespace App\Models;

use App\Enums\AppointmentLocation;
use Database\Factories\AppointmentTypeFactory;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/** Un type de rendez-vous proposé aux visiteurs : sa durée et les lieux possibles. */
class AppointmentType extends Model
{
    /** @use HasFactory<AppointmentTypeFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
        'duration_minutes',
        'locations',
        'is_active',
        'sort_order',
    ];

    /** @var array<string, mixed> */
    protected $casts = [
        'duration_minutes' => 'integer',
        'locations' => AsEnumCollection::class.':'.AppointmentLocation::class,
        'is_active' => 'boolean',
    ];

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
