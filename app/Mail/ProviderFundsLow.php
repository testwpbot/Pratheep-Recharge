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
            ? 'Provider funds low — pay ' . $pay['currency'] . ' ' . number_format((float) $pay['amount'], 2) . ' to ' . $pay['provider']
            : 'Provider funds low — top up API wallets';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provider_funds_low',
            with: ['h' => $this->health],
        );
    }
}
