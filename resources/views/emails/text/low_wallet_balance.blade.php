Your wallet is low

Hi {{ explode(' ', trim($user->name))[0] }},

Your Happy Pratheep wallet is below the minimum. Add money so you can keep recharging and paying bills.

Wallet now: LKR {{ number_format((float) $balance, 2) }}
You need at least: LKR {{ number_format((float) $min, 2) }}

Send a bank transfer of LKR {{ number_format((float) $min, 2) }} or more. We will add it after we check the slip.

Add money: {{ route('wallet') }}

Happy Pratheep Recharge
