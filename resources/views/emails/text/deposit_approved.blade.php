Your deposit is approved

Hi {{ explode(' ', trim($d->user->name))[0] }},

We verified your bank transfer. LKR {{ number_format((float) $d->amount, 2) }} is now in your Happy Pratheep wallet.

Reference: {{ $d->reference() }}
@if($d->admin_note)
Note: {{ $d->admin_note }}
@endif

Open wallet: {{ route('wallet') }}

Happy Pratheep Recharge
