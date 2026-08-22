<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProviderFundsLow extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string,mixed>  $health */
    public function __construct(public array $health) {}

    public function envelope(): Envelope
    {
        $pay = $this->health['pay'][0] ?? null;
        $subject = $pay
            ? 'Provider money is low — add ' . $pay['currency'] . ' ' . number_format((float) $pay['amount'], 2) . ' to ' . $pay['provider']
            : 'Provider money is low — add money to the provider wallet';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.provider_funds_low',
            text: 'emails.text.provider_funds_low',
            with: ['h' => $this->health],
        );
    }
}
