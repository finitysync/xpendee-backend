<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractSignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Contract $contract,
        public readonly Tenant   $tenant,
        public readonly string   $pdf,
        public readonly string   $recipient = 'agency', // 'agency' | 'client'
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->recipient === 'agency'
            ? "✅ Contract Signed: {$this->contract->title}"
            : "Your signed contract: {$this->contract->title}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-signed',
            with: [
                'contract'  => $this->contract,
                'tenant'    => $this->tenant,
                'recipient' => $this->recipient,
            ]
        );
    }

    public function attachments(): array
    {
        $filename = 'Contract_' . str_replace(' ', '_', $this->contract->title) . '.pdf';

        return [
            Attachment::fromData(fn () => $this->pdf, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
