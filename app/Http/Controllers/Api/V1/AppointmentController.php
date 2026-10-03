<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentDecisionRequest;
use App\Http\Resources\Api\V1\AppointmentResource;
use App\Models\Appointment;
use App\Services\BookingCalendar;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Rendez-vous
 */
class AppointmentController extends Controller
{
    use PaginatesAdminLists;

    public function __construct(private BookingCalendar $calendar) {}

    /**
     * Liste des rendez-vous
     */
    #[QueryParameter('search', description: 'Recherche dans le nom, l’e-mail, la société et le téléphone.', type: 'string')]
    #[QueryParameter('status', description: 'Statut : `pending`, `confirmed`, `declined` ou `cancelled`.', type: 'string')]
    #[QueryParameter('location', description: 'Lieu : `video`, `phone`, `whatsapp` ou `in_person`.', type: 'string')]
    #[QueryParameter('page', description: 'Numéro de page.', type: 'integer', default: 1)]
    #[QueryParameter('per_page', description: 'Éléments par page : `10`, `25` ou `50`.', type: 'integer', default: 10)]
    public function index(Request $request): AnonymousResourceCollection
    {
        return AppointmentResource::collection(
            $this->paginateList(Appointment::query()->with('appointmentType')->latest('starts_at'), $request, ['name', 'email', 'company', 'phone'], ['status', 'location'])
        );
    }

    /**
     * Détail d'un rendez-vous
     */
    public function show(Appointment $appointment): AppointmentResource
    {
        return new AppointmentResource($appointment->load('appointmentType'));
    }

    /**
     * Confirmer un rendez-vous en attente
     *
     * Sans `meeting_details`, une visio reprend le lien visio par défaut des réglages.
     */
    public function confirm(AppointmentDecisionRequest $request, Appointment $appointment): AppointmentResource
    {
        $this->calendar->confirm($appointment, $request->validated('meeting_details'));

        return new AppointmentResource($appointment->refresh()->load('appointmentType'));
    }

    /**
     * Refuser un rendez-vous
     *
     * Le créneau est libéré.
     */
    public function decline(AppointmentDecisionRequest $request, Appointment $appointment): AppointmentResource
    {
        $this->calendar->decline($appointment, $request->validated('decline_reason'));

        return new AppointmentResource($appointment->refresh()->load('appointmentType'));
    }

    /**
     * Supprimer un rendez-vous
     */
    public function destroy(Appointment $appointment): Response
    {
        $this->calendar->release($appointment);
        $appointment->delete();

        return response()->noContent();
    }
}
