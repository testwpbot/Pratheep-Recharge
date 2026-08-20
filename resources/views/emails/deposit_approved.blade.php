<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Deposit Approved ✅</title></head>
<body style="margin:0; padding:0; background:#f6f8fc; font-family:Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fc; padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="580" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 12px 30px rgba(7,27,61,.12);">
  <tr><td style="background:linear-gradient(135deg,#22c55e,#16a34a); padding:28px; color:#fff; text-align:center;">
    <div style="font-size:44px; margin-bottom:8px;">✅</div>
    <h1 style="margin:0; font-size:22px;">Deposit Approved!</h1>
  </td></tr>
  <tr><td style="padding:28px; color:#0b2a5b; font-size:14px; line-height:1.6;">
    <p style="margin:0 0 18px;">Hi {{ $d->user->name }},</p>
    <p style="margin:0 0 18px;">Great news! Your wallet deposit has been verified and approved. The funds are now available in your Happy Pratheep Recharge wallet.</p>
    <table role="presentation" width="100%" cellpadding="12" cellspacing="0" style="background:#fff9ec; border:1px solid rgba(232,163,23,.35); border-radius:12px;">
      <tr><td style="color:#8a6f00; font-weight:700; width:160px;">Reference</td><td style="font-weight:800;">{{ $d->reference() }}</td></tr>
      <tr><td style="color:#8a6f00; font-weight:700;">Amount Credited</td><td style="font-weight:800; color:#0b2a5b; font-size:20px;">LKR {{ number_format($d->amount, 2) }}</td></tr>
      @if($d->admin_note)
      <tr><td style="color:#8a6f00; font-weight:700;">Admin Note</td><td style="font-weight:600;">{{ $d->admin_note }}</td></tr>
      @endif
    </table>
    <p style="margin:20px 0 0; text-align:center;">
      <a href="{{ route('wallet') }}" style="display:inline-block; padding:12px 22px; background:linear-gradient(135deg,#0b2a5b,#071b3d); color:#fff; font-weight:800; text-decoration:none; border-radius:10px;">Go to My Wallet →</a>
    </p>
    <p style="margin:18px 0 0; font-size:12px; color:#8a94a8; text-align:center;">Thanks for recharging with Happy Pratheep! ⚡</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
