<?php

namespace App\Mail;

use App\Models\WalletDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DepositApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WalletDeposit $deposit) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Wallet Deposit Has Been Approved');
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.deposit_approved',
            text: 'emails.text.deposit_approved',
            with: ['d' => $this->deposit],
        );
    }
}
