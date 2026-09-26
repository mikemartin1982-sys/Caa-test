<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Michael, 2026-09-06 -- VEO Expertise request form (VEO Law-Related
 * Services page). Same real pattern as PrivateSmokeSchoolInquiry: a
 * real, internal staff notification only -- no database record at
 * all, sent via Laravel's own standard Mail system, real SMTP
 * credentials still need to be added to .env before this actually
 * sends anything.
 */
class VeoExpertiseInquiry extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $inquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'VEO Expertise Request -- ' . ($this->inquiry['company'] ?? 'Unknown Company'),
            replyTo: [$this->inquiry['email']],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.veo-expertise-inquiry',
        );
    }
}
