New wallet deposit to review

A customer submitted a bank transfer for their wallet.

Reference: {{ $d->reference() }}
Customer: {{ $d->user->name }}
Email: {{ $d->user->email }}
Phone: {{ $d->user->phone ?: '—' }}
Amount: LKR {{ number_format((float) $d->amount, 2) }}
Bank: {{ $d->bank_name }}
Depositor: {{ $d->depositor_name }}
@if($d->reference_number)
Bank reference: {{ $d->reference_number }}
@endif
Submitted: {{ $d->created_at->format('Y-m-d H:i') }}

The bank slip is attached when the customer uploaded one.

Review deposit: {{ route('admin.deposits.show', $d) }}

Happy Pratheep Recharge
