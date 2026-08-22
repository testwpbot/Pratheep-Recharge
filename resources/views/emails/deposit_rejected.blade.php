@extends('emails.layout')

@section('title', 'Wallet deposit not approved')
@section('preheader', 'We could not approve deposit ' . $d->reference() . '.')
@section('eyebrow', $d->reference())
@section('heading', 'We could not approve this deposit')

@section('content')
  <p style="margin:0 0 14px;">Hi {{ explode(' ', trim($d->user->name))[0] }},</p>
  <p style="margin:0 0 16px;">Your wallet deposit was not approved. Nothing was added to your balance, and no money was taken from your wallet.</p>
  @include('emails.partials.rows', ['rows' => [
      ['label' => 'Reference', 'value' => $d->reference()],
      ['label' => 'Amount', 'value' => 'LKR ' . number_format((float) $d->amount, 2)],
      ['label' => 'Reason', 'value' => $d->admin_note ?: 'The bank slip could not be verified. Please contact support.'],
  ]])
  <p style="margin:16px 0 0;">If this looks wrong, send a clearer slip or write to support with the reference above.</p>
@endsection

@section('action')
  @include('emails.partials.button', ['url' => route('wallet'), 'label' => 'Submit again'])
@endsection

@section('footer')
  You received this because a wallet deposit on your account was reviewed.
@endsection
