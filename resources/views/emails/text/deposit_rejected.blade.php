We could not approve this deposit

Hi {{ explode(' ', trim($d->user->name))[0] }},

Your wallet deposit was not approved. Nothing was added to your balance.

Reference: {{ $d->reference() }}
Amount: LKR {{ number_format((float) $d->amount, 2) }}
Reason: {{ $d->admin_note ?: 'The bank slip could not be verified. Please contact support.' }}

Submit again: {{ route('wallet') }}

Happy Pratheep Recharge
