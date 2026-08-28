Add money to keep recharging

Hi {{ explode(' ', trim($user->name))[0] }},

You must keep LKR {{ number_format((float) $min, 2) }} in your wallet. Add more money if you want to place a recharge.

Wallet now: LKR {{ number_format((float) $balance, 2) }}
Must stay in wallet: LKR {{ number_format((float) $min, 2) }}

A LKR 50 recharge needs LKR {{ number_format((float) $min + 50, 2) }} in your wallet. Send a bank transfer. We will add it after we check the slip.

Add money: {{ route('wallet') }}

Happy Pratheep Recharge
