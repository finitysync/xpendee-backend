<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractSigningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Contract $contract,
        public readonly Tenant   $tenant,
        public readonly string   $signingUrl,
        public readonly string   $subject  = '',
        public readonly string   $message  = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject ?: "Please sign: {$this->contract->title}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-signing',
            with: [
                'contract'   => $this->contract,
                'tenant'     => $this->tenant,
                'signingUrl' => $this->signingUrl,
                'message'    => $this->message,
            ]
        );
    }
}
