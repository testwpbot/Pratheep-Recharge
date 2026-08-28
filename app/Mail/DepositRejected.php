<?php

namespace App\Mail;

use App\Models\WalletDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DepositRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WalletDeposit $deposit) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Wallet Deposit Was Not Approved');
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.deposit_rejected',
            text: 'emails.text.deposit_rejected',
            with: ['d' => $this->deposit],
        );
    }
}
