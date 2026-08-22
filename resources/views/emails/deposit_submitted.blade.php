@extends('emails.layout')

@section('title', 'New wallet deposit')
@section('preheader', $d->user->name . ' submitted LKR ' . number_format((float) $d->amount, 2) . '.')
@section('eyebrow', $d->reference())
@section('heading', 'New wallet deposit to review')

@section('content')
  <p style="margin:0 0 16px;">A customer submitted a bank transfer for their wallet. Please check the slip and approve or decline it.</p>
  @include('emails.partials.rows', ['rows' => array_values(array_filter([
      ['label' => 'Customer', 'value' => $d->user->name],
      ['label' => 'Email', 'value' => $d->user->email],
      ['label' => 'Phone', 'value' => $d->user->phone ?: '—'],
      ['label' => 'Amount', 'value' => 'LKR ' . number_format((float) $d->amount, 2), 'large' => true],
      ['label' => 'Bank', 'value' => $d->bank_name],
      ['label' => 'Depositor', 'value' => $d->depositor_name],
      $d->reference_number ? ['label' => 'Bank reference', 'value' => $d->reference_number] : null,
      ['label' => 'Submitted', 'value' => $d->created_at->format('Y-m-d H:i')],
  ]))])
  <p style="margin:16px 0 0;font-size:13px;color:#71717a;">The bank slip is attached when the customer uploaded one.</p>
@endsection

@section('action')
  @include('emails.partials.button', ['url' => route('admin.deposits.show', $d), 'label' => 'Review deposit'])
@endsection

@section('footer')
  You received this because you are an admin on Happy Pratheep Recharge.
@endsection
