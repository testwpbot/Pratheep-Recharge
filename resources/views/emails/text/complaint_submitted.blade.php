New customer complaint

Reference: {{ $c->reference }}
Customer: {{ $c->user->name }} ({{ $c->user->email }})
Order: @if($c->order){{ $c->order->reference }} — {{ optional($c->order->service)->name }} — LKR {{ number_format((float) $c->order->amount, 2) }}@else—@endif
Mobile: {{ $c->mobile ?: '—' }}
Subject: {{ $c->subject }}

Message:
{{ $c->reason }}

Open complaint: {{ route('admin.complaints.show', $c) }}

Happy Pratheep Recharge
