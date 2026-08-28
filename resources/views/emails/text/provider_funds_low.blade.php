Provider wallet is low

The provider has less money than customers currently have on the site. Add money to the provider so recharges do not fail.

Customers have: LKR {{ number_format((float) ($h['user_total'] ?? 0), 2) }}
Provider has (LKR): @if(($h['combined_lkr'] ?? null) === null)Cannot check@else LKR {{ number_format((float) $h['combined_lkr'], 2) }}@endif

@foreach (($h['pay'] ?? []) as $p)
Add {{ $p['currency'] }} {{ number_format((float) $p['amount'], 2) }} to {{ $p['provider'] }}
{{ $p['reason'] }}

@endforeach
@foreach (($h['providers'] ?? []) as $r)
{{ $r['name'] }}: @if($r['balance'] === null){{ $r['error_label'] ?? 'Cannot check' }}@else{{ $r['currency'] }} {{ number_format((float) $r['balance'], 2) }}@endif
@endforeach

Open provider money: {{ route('admin.funds.index') }}

Happy Pratheep Recharge
