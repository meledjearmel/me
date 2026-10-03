<?php

namespace App\Models;

use App\Enums\AppointmentLocation;
use App\Enums\AppointmentStatus;
use App\Services\BookingCalendar;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Zap\Exceptions\ScheduleConflictException;
use Zap\Models\Schedule;

/**
 * Une demande de rendez-vous déposée depuis le site. Elle reste « en attente »
 * jusqu'à ce que je la confirme ou la refuse ; le visiteur peut l'annuler avec
 * le lien reçu par email (cancel_token).
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'appointment_type_id',
        'schedule_id',
        'name',
        'email',
        'phone',
        'company',
        'location',
        'message',
        'starts_at',
        'ends_at',
        'timezone',
        'locale',
        'status',
        'meeting_details',
        'decline_reason',
        'confirmed_at',
        'cancelled_at',
        'reminded_at',
        'ip_address',
        'user_agent',
    ];

    /** @var list<string> */
    protected $hidden = ['cancel_token'];

    /** @var array<string, string> */
    protected $casts = [
        'location' => AppointmentLocation::class,
        'status' => AppointmentStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment): void {
            $appointment->cancel_token ??= Str::random(48);
        });

        // Restauré depuis la corbeille : il reprend son créneau, seulement si la plage
        // est encore libre (sinon il revient sans bloquer l'agenda).
        static::restored(function (Appointment $appointment): void {
            if (! $appointment->status->holdsSlot() || $appointment->schedule_id !== null || $appointment->starts_at->isPast()) {
                return;
            }

            try {
                app(BookingCalendar::class)->hold($appointment);
            } catch (ScheduleConflictException) {
                // Plage déjà occupée par un autre rendez-vous ou une période bloquée.
            }
        });
    }

    /** @return BelongsTo<AppointmentType, $this> */
    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class)->withTrashed();
    }

    /** @return BelongsTo<Schedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
