<?php

namespace App\Mail;

use App\Models\WalletDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DepositSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WalletDeposit $deposit) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New Wallet Deposit Request - ' . $this->deposit->reference());
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deposit_submitted',
            with: ['d' => $this->deposit],
        );
    }

    public function attachments(): array
    {
        $path = $this->deposit->slip_path ? storage_path('app/public/' . $this->deposit->slip_path) : null;
        if ($path && file_exists($path)) {
            return [Attachment::fromPath($path)];
        }
        return [];
    }
}
