<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Internal alert so staff see new inquiries without polling the inbox. Contains no message body (PII minimisation). */
class NewInquiryMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('New inquiry: :type', ['type' => $this->lead->request_type->label()]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-inquiry', with: ['url' => url('/admin/leads/'.$this->lead->id.'/edit')]);
    }
}
