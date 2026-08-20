<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComplaintSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Complaint: ' . $this->complaint->reference . ' — ' . $this->complaint->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.complaint_submitted',
            with: ['c' => $this->complaint->load('user', 'order')],
        );
    }
}
