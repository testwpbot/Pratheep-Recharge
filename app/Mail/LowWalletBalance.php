<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowWalletBalance extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Wallet $wallet,
        public float $minBalance,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your wallet is low — add money to keep recharging');
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.low_wallet_balance',
            text: 'emails.text.low_wallet_balance',
            with: [
                'user'    => $this->user,
                'wallet'  => $this->wallet,
                'min'     => $this->minBalance,
                'balance' => (float) $this->wallet->balance,
            ],
        );
    }
}
