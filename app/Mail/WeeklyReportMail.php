<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

/**
 * Le rapport de la semaine du portfolio, envoyé au propriétaire du site chaque lundi.
 */
class WeeklyReportMail extends Mailable
{
    use Queueable;

    /** @param  array<string, mixed>  $report  Voir WeeklyReport::build(). */
    public function __construct(public array $report) {}

    public function envelope(): Envelope
    {
        $from = Carbon::parse($this->report['period']['from'])->locale('fr')->isoFormat('D MMM');
        $to = Carbon::parse($this->report['period']['to'])->locale('fr')->isoFormat('D MMM');
        $visits = $this->report['audience']['visits'];
        $requests = $this->report['requests']['total'];

        return new Envelope(
            subject: "Ta semaine du {$from} au {$to} : {$visits} visites, {$requests} demande".($requests > 1 ? 's' : ''),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.weekly-report', with: ['report' => $this->report]);
    }
}
