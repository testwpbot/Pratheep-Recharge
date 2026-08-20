<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:24px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 14px rgba(11,42,91,.08);">
        <tr><td style="background:#0b2a5b;padding:24px 32px;color:#fff;">
          @if($c->status === 'resolved')
            <h1 style="margin:0;font-size:22px;">Your complaint has been resolved ✅</h1>
          @elseif($c->status === 'rejected')
            <h1 style="margin:0;font-size:22px;">Update on your complaint</h1>
          @else
            <h1 style="margin:0;font-size:22px;">Your complaint is being reviewed</h1>
          @endif
          <p style="margin:6px 0 0;opacity:.85;font-size:13px;">{{ $c->reference }}</p>
        </td></tr>
        <tr><td style="padding:28px 32px;color:#142c52;font-size:14px;line-height:1.6;">
          <p>Hi {{ explode(' ', $c->user->name)[0] }},</p>

          @if($c->status === 'resolved')
            <p>Good news! We've reviewed your complaint regarding <b>{{ $c->subject }}</b> and the issue has been resolved.</p>
          @elseif($c->status === 'rejected')
            <p>We've reviewed your complaint regarding <b>{{ $c->subject }}</b> and here's our reply:</p>
          @else
            <p>Our team has started reviewing your complaint regarding <b>{{ $c->subject }}</b>. We'll get back to you shortly.</p>
          @endif

          @if($c->admin_note)
            <div style="background:#f7f9fd;border-left:3px solid #e8a317;padding:12px 16px;border-radius:6px;margin:16px 0;">
              {!! nl2br(e($c->admin_note)) !!}
            </div>
          @endif

          <p style="margin-top:24px;">
            <a href="{{ route('complaints.show', $c) }}"
               style="display:inline-block;background:#e8a317;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700;">
               View complaint details
            </a>
          </p>

          <p style="color:#7a8599;font-size:12px;margin-top:20px;">— Happy Pratheep Recharge Support</p>
        </td></tr>
        <tr><td style="padding:16px 32px;background:#f7f9fd;color:#7a8599;font-size:12px;">
          You're receiving this because you submitted a complaint on {{ config('app.name') }}.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
