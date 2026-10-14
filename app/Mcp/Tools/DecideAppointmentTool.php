<?php

namespace App\Mcp\Tools;

use App\Models\Appointment;
use App\Services\BookingCalendar;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Name('decide-appointment')]
#[Description('Confirme ou refuse un rendez-vous en attente (voir get-inbox). Le visiteur reçoit aussitôt l\'email habituel (confirmation avec les détails, ou refus avec la raison) et le créneau est bloqué ou libéré : ne l\'appelle que sur demande explicite du développeur pour ce rendez-vous.')]
class DecideAppointmentTool extends Tool
{
    public function handle(Request $request, BookingCalendar $calendar): Response
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'decision' => ['required', 'in:confirm,decline'],
            'meeting_details' => ['nullable', 'string', 'max:2000'],
            'decline_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $appointment = Appointment::query()->find($data['id']);

        if ($appointment === null) {
            return Response::error('Rendez-vous introuvable.');
        }

        try {
            $data['decision'] === 'confirm'
                ? $calendar->confirm($appointment, $data['meeting_details'] ?? null)
                : $calendar->decline($appointment, $data['decline_reason'] ?? null);
        } catch (HttpException $exception) {
            return Response::error($exception->getMessage());
        }

        return Response::text(($data['decision'] === 'confirm' ? 'Rendez-vous confirmé' : 'Rendez-vous refusé')
            ." : {$appointment->name} a été prévenu par email. ".route('admin.appointments.show', $appointment));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Le rendez-vous (appointments de get-inbox).')->required(),
            'decision' => $schema->string()->enum(['confirm', 'decline'])->required(),
            'meeting_details' => $schema->string()->description('Confirmation : ce qu\'il faut savoir (lien, adresse, numéro…), ajouté à l\'email ; sinon les détails par défaut du type de rendez-vous.'),
            'decline_reason' => $schema->string()->description('Refus : la raison, polie et courte, transmise au visiteur.'),
        ];
    }
}
