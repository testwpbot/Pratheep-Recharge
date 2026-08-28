@php
  $first = explode(' ', trim($c->user->name))[0];
  $heading = match ($c->status) {
      'resolved'    => 'Your complaint is resolved',
      'rejected'    => 'Update on your complaint',
      'in_progress' => 'We are reviewing your complaint',
      default       => 'Update on your complaint',
  };
@endphp
{{ $heading }}

Hi {{ $first }},

Reference: {{ $c->reference }}
Subject: {{ $c->subject }}
Status: {{ $c->statusLabel() }}
@if($c->admin_note)

Reply:
{{ $c->admin_note }}
@endif

View complaint: {{ route('complaints.show', $c) }}

Happy Pratheep Recharge
