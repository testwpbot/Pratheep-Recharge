<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:24px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 14px rgba(11,42,91,.08);">
        <tr><td style="background:#0b2a5b;padding:24px 32px;color:#fff;">
          <h1 style="margin:0;font-size:22px;">New customer complaint</h1>
          <p style="margin:6px 0 0;opacity:.85;font-size:13px;">{{ config('app.name') }} admin panel</p>
        </td></tr>
        <tr><td style="padding:28px 32px;color:#142c52;font-size:14px;line-height:1.6;">
          <p><b>Reference:</b> {{ $c->reference }}</p>
          <p><b>Customer:</b> {{ $c->user->name }} ({{ $c->user->email }})</p>
          <p><b>Order:</b>
            @if($c->order)
              {{ $c->order->reference }} — {{ optional($c->order->service)->name }} — LKR {{ number_format((float) $c->order->amount, 2) }}
            @else
              —
            @endif
          </p>
          <p><b>Mobile:</b> {{ $c->mobile ?: '—' }}</p>
          <p><b>Subject:</b> {{ $c->subject }}</p>
          <p><b>Message:</b></p>
          <div style="background:#f7f9fd;border-left:3px solid #e8a317;padding:12px 16px;border-radius:6px;">{{ $c->reason }}</div>

          <p style="margin-top:24px;">
            <a href="{{ route('admin.complaints.show', $c) }}"
               style="display:inline-block;background:#e8a317;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700;">
               View in admin panel
            </a>
          </p>
        </td></tr>
        <tr><td style="padding:16px 32px;background:#f7f9fd;color:#7a8599;font-size:12px;">
          Sent from {{ config('app.name') }}
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
