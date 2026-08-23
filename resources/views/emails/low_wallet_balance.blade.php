@extends('emails.layout')

@section('title', 'Your wallet is low')
@section('preheader', 'You must keep LKR ' . number_format((float) $min, 2) . ' in your wallet. Add money to recharge.')
@section('heading', 'Add money to keep recharging')

@section('content')
  <p style="margin:0 0 14px;">Hi {{ explode(' ', trim($user->name))[0] }},</p>
  <p style="margin:0 0 16px;">You must keep LKR {{ number_format((float) $min, 2) }} in your wallet. Add more money if you want to place a recharge.</p>
  @include('emails.partials.rows', ['rows' => [
      ['label' => 'Wallet now', 'value' => 'LKR ' . number_format((float) $balance, 2), 'large' => true],
      ['label' => 'Must stay in wallet', 'value' => 'LKR ' . number_format((float) $min, 2)],
  ]])
  <p style="margin:16px 0 0;">A LKR 50 recharge needs LKR {{ number_format((float) $min + 50, 2) }} in your wallet. Send a bank transfer, then we will add it after we check the slip.</p>
@endsection

@section('action')
  @include('emails.partials.button', ['url' => route('wallet'), 'label' => 'Add money to wallet'])
@endsection

@section('signoff')
  Happy Pratheep Recharge
@endsection

@section('footer')
  You received this because your wallet dropped below the minimum on Happy Pratheep Recharge.
@endsection
