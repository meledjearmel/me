<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AvailabilityRuleRequest;
use App\Http\Requests\Admin\BlockedPeriodRequest;
use App\Http\Requests\Admin\BookingSettingsRequest;
use App\Services\BookingCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Zap\Models\Schedule;

/**
 * @tags Disponibilités
 */
class AvailabilityController extends Controller
{
    public function __construct(private BookingCalendar $calendar) {}

    /**
     * Réglages, plages hebdomadaires et périodes bloquées
     *
     * Les heures sont celles d'Abidjan (UTC).
     */
    public function index(): JsonResponse
    {
        return response()->json($this->payload());
    }

    /**
     * Modifier les réglages de réservation
     */
    public function updateSettings(BookingSettingsRequest $request): JsonResponse
    {
        $this->calendar->settings()->update($request->validated());

        return response()->json($this->payload());
    }

    /**
     * Ajouter une plage de disponibilité hebdomadaire
     */
    public function storeRule(AvailabilityRuleRequest $request): JsonResponse
    {
        $this->calendar->addAvailability($request->validated('days'), $request->validated('start'), $request->validated('end'));

        return response()->json($this->payload(), 201);
    }

    /**
     * Ajouter une période bloquée
     */
    public function storeBlockedPeriod(BlockedPeriodRequest $request): JsonResponse
    {
        $this->calendar->addBlockedPeriod($request->validated('from'), $request->validated('to'), $request->validated('label'));

        return response()->json($this->payload(), 201);
    }

    /**
     * Retirer une plage de disponibilité ou une période bloquée
     */
    public function destroy(Schedule $schedule): Response
    {
        $this->calendar->removeRule($schedule);

        return response()->noContent();
    }

    /**
     * @return array{settings: array{is_enabled: bool, min_notice_hours: int, horizon_days: int, buffer_minutes: int, video_link: string|null}, rules: list<array{id: int, days: list<string>, start: string, end: string}>, blocked_periods: list<array{id: int, label: string|null, from: string, to: string}>}
     */
    private function payload(): array
    {
        return [
            'settings' => $this->calendar->settings()->only(['is_enabled', 'min_notice_hours', 'horizon_days', 'buffer_minutes', 'video_link']),
            'rules' => $this->calendar->availabilityRules()->all(),
            'blocked_periods' => $this->calendar->blockedPeriods()->all(),
        ];
    }
}
