<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Tenant  $tenant,
        public readonly string  $pdf,
        public readonly string  $subject = '',
        public readonly string  $body    = '',
    ) {}

    public function envelope(): Envelope
    {
        $subj = $this->subject ?: "Invoice #{$this->invoice->number} from {$this->tenant->app_name}";

        return new Envelope(subject: $subj);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'tenant'  => $this->tenant,
                'body'    => $this->body,
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, "Invoice_{$this->invoice->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
