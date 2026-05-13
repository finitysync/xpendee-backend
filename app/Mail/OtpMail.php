<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp,
        public readonly string $contractTitle,
        public readonly mixed  $tenant = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Signing OTP Code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otp'           => $this->otp,
                'contractTitle' => $this->contractTitle,
                'tenant'        => $this->tenant,
            ]
        );
    }
}
