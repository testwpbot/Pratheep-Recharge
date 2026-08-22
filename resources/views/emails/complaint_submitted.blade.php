@extends('emails.layout')

@section('title', 'New complaint')
@section('preheader', $c->reference . ' — ' . $c->subject)
@section('eyebrow', $c->reference)
@section('heading', 'New customer complaint')

@section('content')
  <p style="margin:0 0 16px;">A customer opened a complaint. Details are below.</p>
  @include('emails.partials.rows', ['rows' => [
      ['label' => 'Customer', 'value' => $c->user->name . ' (' . $c->user->email . ')'],
      ['label' => 'Order', 'value' => $c->order
          ? $c->order->reference . ' — ' . optional($c->order->service)->name . ' — LKR ' . number_format((float) $c->order->amount, 2)
          : '—'],
      ['label' => 'Mobile', 'value' => $c->mobile ?: '—'],
      ['label' => 'Subject', 'value' => $c->subject],
      ['label' => 'Message', 'value' => $c->reason],
  ]])
@endsection

@section('action')
  @include('emails.partials.button', ['url' => route('admin.complaints.show', $c), 'label' => 'Open complaint'])
@endsection

@section('footer')
  You received this because you are an admin on Happy Pratheep Recharge.
@endsection
