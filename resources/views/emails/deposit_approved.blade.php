@extends('emails.layout')

@section('title', 'Wallet deposit approved')
@section('preheader', 'LKR ' . number_format((float) $d->amount, 2) . ' has been added to your wallet.')
@section('eyebrow', $d->reference())
@section('heading', 'Your deposit is approved')

@section('content')
  <p style="margin:0 0 14px;">Hi {{ explode(' ', trim($d->user->name))[0] }},</p>
  <p style="margin:0 0 16px;">We verified your bank transfer. The amount is now in your Happy Pratheep wallet and ready to use.</p>
  @include('emails.partials.rows', ['rows' => array_values(array_filter([
      ['label' => 'Reference', 'value' => $d->reference()],
      ['label' => 'Amount added', 'value' => 'LKR ' . number_format((float) $d->amount, 2), 'large' => true],
      $d->admin_note ? ['label' => 'Note', 'value' => $d->admin_note] : null,
  ]))])
@endsection

@section('action')
  @include('emails.partials.button', ['url' => route('wallet'), 'label' => 'Open wallet'])
@endsection

@section('signoff')
  Thank you for using Happy Pratheep Recharge.
@endsection

@section('footer')
  You received this because a wallet deposit on your account was approved.
@endsection
