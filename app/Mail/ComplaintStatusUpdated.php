<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComplaintStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->complaint->status) {
            'resolved'    => 'Your complaint ' . $this->complaint->reference . ' has been resolved ✅',
            'rejected'    => 'Update on your complaint ' . $this->complaint->reference,
            'in_progress' => 'Your complaint ' . $this->complaint->reference . ' is being reviewed',
            default       => 'Update on your complaint ' . $this->complaint->reference,
        };
        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.complaint_status_updated',
            with: ['c' => $this->complaint->load('user', 'order', 'handler')],
        );
    }
}
