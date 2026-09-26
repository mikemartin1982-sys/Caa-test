<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Michael, 2026-09-05 -- Private Smoke School inquiry form. Confirmed
 * with Michael: this is a real, internal staff notification only --
 * NOT a database record at all (staff already have real templates to
 * respond manually) and NOT routed through Brevo (not yet configured
 * CRM-like for this). Uses Laravel's own, standard, .env-configurable
 * Mail system instead -- the simplest, most direct path, matching the
 * same "build against the real interface now, wire up final
 * credentials later" pattern already used throughout this project.
 *
 * Confirmed with Michael: goes to client@compliance-assurance.com,
 * NOT info@ (the general contact address already used elsewhere on
 * the real, live site).
 */
class PrivateSmokeSchoolInquiry extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $inquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Private Smoke School Request -- ' . ($this->inquiry['company'] ?? 'Unknown Company'),
            replyTo: [$this->inquiry['email']],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.private-smoke-school-inquiry',
        );
    }
}
