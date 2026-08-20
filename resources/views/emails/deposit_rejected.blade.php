<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Deposit Not Approved</title></head>
<body style="margin:0; padding:0; background:#f6f8fc; font-family:Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fc; padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="580" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 12px 30px rgba(7,27,61,.12);">
  <tr><td style="background:linear-gradient(135deg,#d43b3b,#b42f2f); padding:28px; color:#fff; text-align:center;">
    <div style="font-size:44px; margin-bottom:8px;">⚠️</div>
    <h1 style="margin:0; font-size:22px;">Deposit Not Approved</h1>
  </td></tr>
  <tr><td style="padding:28px; color:#0b2a5b; font-size:14px; line-height:1.6;">
    <p style="margin:0 0 18px;">Hi {{ $d->user->name }},</p>
    <p style="margin:0 0 18px;">We're sorry, but your recent wallet deposit request could not be approved. No funds were deducted and no balance was added to your wallet.</p>
    <table role="presentation" width="100%" cellpadding="12" cellspacing="0" style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px;">
      <tr><td style="color:#b42f2f; font-weight:700; width:160px;">Reference</td><td style="font-weight:800;">{{ $d->reference() }}</td></tr>
      <tr><td style="color:#b42f2f; font-weight:700;">Amount</td><td style="font-weight:800;">LKR {{ number_format($d->amount, 2) }}</td></tr>
      <tr><td style="color:#b42f2f; font-weight:700;">Reason</td><td style="font-weight:600;">{{ $d->admin_note ?: 'The bank slip could not be verified. Please contact support.' }}</td></tr>
    </table>
    <p style="margin:20px 0 0;">If you believe this was an error, double-check the bank slip and try again, or contact our support team with the reference number above.</p>
    <p style="margin:18px 0 0; text-align:center;">
      <a href="{{ route('wallet') }}" style="display:inline-block; padding:12px 22px; background:linear-gradient(135deg,#0b2a5b,#071b3d); color:#fff; font-weight:800; text-decoration:none; border-radius:10px;">Try Again →</a>
    </p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
