@extends('emails.layout')

@section('title')
  @if($c->status === 'resolved') Your complaint is resolved
  @elseif($c->status === 'rejected') Update on your complaint
  @elseif($c->status === 'in_progress') We are reviewing your complaint
  @else Update on your complaint
  @endif
@endsection

@section('preheader', $c->reference . ' — ' . $c->statusLabel())
@section('eyebrow', $c->reference)

@section('heading')
  @if($c->status === 'resolved') Your complaint is resolved
  @elseif($c->status === 'rejected') Update on your complaint
  @elseif($c->status === 'in_progress') We are reviewing your complaint
  @else Update on your complaint
  @endif
@endsection

@section('content')
  <p style="margin:0 0 14px;">Hi {{ explode(' ', trim($c->user->name))[0] }},</p>
  @if($c->status === 'resolved')
    <p style="margin:0 0 16px;">We reviewed your complaint about {{ $c->subject }} and marked it as resolved.</p>
  @elseif($c->status === 'rejected')
    <p style="margin:0 0 16px;">We reviewed your complaint about {{ $c->subject }}. Our reply is below.</p>
  @elseif($c->status === 'in_progress')
    <p style="margin:0 0 16px;">Our team has started reviewing your complaint about {{ $c->subject }}.</p>
  @else
    <p style="margin:0 0 16px;">There is an update on your complaint about {{ $c->subject }}.</p>
  @endif
  @if($c->admin_note)
    @include('emails.partials.rows', ['rows' => [
        ['label' => 'Reply', 'value' => $c->admin_note],
    ]])
  @endif
@endsection

@section('action')
  @include('emails.partials.button', ['url' => route('complaints.show', $c), 'label' => 'View complaint'])
@endsection

@section('footer')
  You received this because you submitted a complaint on Happy Pratheep Recharge.
@endsection
