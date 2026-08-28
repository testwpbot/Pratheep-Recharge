@extends('emails.layout')

@section('title', 'Provider wallet is low')
@section('preheader', 'The provider wallet is lower than customer wallets. Add money so recharges can continue.')
@section('heading', 'Provider wallet is low')

@section('content')
  <p style="margin:0 0 16px;">The provider has less money than customers currently have on the site. Add money to the provider so recharges do not fail.</p>

  @include('emails.partials.rows', ['rows' => [
      ['label' => 'Customers have', 'value' => 'LKR ' . number_format((float) ($h['user_total'] ?? 0), 2), 'large' => true],
      ['label' => 'Provider has (LKR)', 'value' => ($h['combined_lkr'] ?? null) === null
          ? 'Cannot check'
          : 'LKR ' . number_format((float) $h['combined_lkr'], 2)],
  ]])

  @foreach (($h['pay'] ?? []) as $p)
    <p style="margin:18px 0 6px;font-size:13px;color:#71717a;">Add this amount</p>
    @include('emails.partials.rows', ['rows' => [
        ['label' => 'Provider', 'value' => $p['provider']],
        ['label' => 'Add', 'value' => $p['currency'] . ' ' . number_format((float) $p['amount'], 2), 'large' => true],
        ['label' => 'Why', 'value' => $p['reason']],
    ]])
  @endforeach

  @if(!empty($h['providers']))
    <p style="margin:18px 0 6px;font-size:13px;color:#71717a;">All providers</p>
    @include('emails.partials.rows', ['rows' => collect($h['providers'])->map(function ($r) {
        $wallet = $r['balance'] === null
            ? ($r['error_label'] ?? 'Cannot check')
            : $r['currency'] . ' ' . number_format((float) $r['balance'], 2);
        $state = ($r['status'] ?? '') === 'low' ? 'Not enough' : (($r['status'] ?? '') === 'healthy' ? 'OK' : 'Cannot check');
        return ['label' => $r['name'], 'value' => $wallet . ' · ' . $state];
    })->all()])
  @endif
@endsection

@section('action')
  @include('emails.partials.button', ['url' => route('admin.funds.index'), 'label' => 'Open provider money'])
@endsection

@section('footer')
  You received this because you are an admin on Happy Pratheep Recharge.
@endsection
