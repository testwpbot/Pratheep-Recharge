<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
        public string $purpose,
        public ?string $ip = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Happy Pratheep verification code');
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.email_otp',
            text: 'emails.text.email_otp',
            with: [
                'user'    => $this->user,
                'code'    => $this->code,
                'purpose' => $this->purpose,
                'ip'      => $this->ip,
            ],
        );
    }
}
