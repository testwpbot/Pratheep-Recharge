@extends('emails.layout')

@section('title', 'Your wallet is low')
@section('preheader', 'Your wallet is below LKR ' . number_format((float) $min, 2) . '. Add money to keep recharging.')
@section('heading', 'Your wallet is low')

@section('content')
  <p style="margin:0 0 14px;">Hi {{ explode(' ', trim($user->name))[0] }},</p>
  <p style="margin:0 0 16px;">Your Happy Pratheep wallet is below the minimum. Add money so you can keep recharging and paying bills.</p>
  @include('emails.partials.rows', ['rows' => [
      ['label' => 'Wallet now', 'value' => 'LKR ' . number_format((float) $balance, 2), 'large' => true],
      ['label' => 'You need at least', 'value' => 'LKR ' . number_format((float) $min, 2)],
  ]])
  <p style="margin:16px 0 0;">Send a bank transfer of LKR {{ number_format((float) $min, 2) }} or more, then we will add it to your wallet after we check the slip.</p>
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
