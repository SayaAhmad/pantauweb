<?php

namespace App\Mail;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Monitor $monitor,
        public readonly Incident $incident,
        public readonly bool $resolved,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->resolved
                ? "[Pantau] Pulih: {$this->monitor->name}"
                : "[Pantau] Gangguan: {$this->monitor->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.incident-alert',
        );
    }
}
