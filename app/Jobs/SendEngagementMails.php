<?php

namespace App\Jobs;

use App\Enums\EngagementType;
use App\Mail\CvMail;
use App\Mail\EngagementReceivedMail;
use App\Models\Engagement;
use App\Models\Profile;
use App\Services\CvGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoie les emails d'une demande de collaboration, en tâche de fond (le
 * visiteur n'attend ni la génération du PDF ni le serveur mail) :
 * - recrutement : le CV adapté au poste, envoyé au recruteur ;
 * - toujours : une notification au propriétaire du site.
 * Un échec d'envoi est journalisé et ne casse jamais la demande, déjà enregistrée.
 */
class SendEngagementMails implements ShouldQueue
{
    use Dispatchable, Queueable;

    /** Un seul essai : les échecs d'envoi sont journalisés, et un second passage renverrait le CV en double. */
    public int $tries = 1;

    public function __construct(public int $engagementId) {}

    public function handle(CvGenerator $cv): void
    {
        $engagement = Engagement::query()->with('jobProfile')->find($this->engagementId);

        if ($engagement === null) {
            return;
        }

        $profile = Profile::query()->first();

        if ($engagement->type === EngagementType::Hiring && $engagement->jobProfile !== null && $profile !== null) {
            try {
                Mail::to($engagement->email, $engagement->name)->send(new CvMail(
                    $engagement,
                    $cv->pdf($engagement->jobProfile, $engagement->locale),
                    $cv->filename($engagement->jobProfile),
                    $profile->name,
                ));

                $engagement->forceFill(['cv_sent_at' => now()])->save();
            } catch (Throwable $exception) {
                Log::error('Envoi du CV impossible', [
                    'engagement' => $engagement->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($profile !== null) {
            try {
                Mail::to($profile->email)->send(new EngagementReceivedMail($engagement));
            } catch (Throwable $exception) {
                Log::error('Notification de demande impossible', [
                    'engagement' => $engagement->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
