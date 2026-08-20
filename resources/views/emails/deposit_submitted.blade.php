<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>New Deposit Request</title></head>
<body style="margin:0; padding:0; background:#f6f8fc; font-family:Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fc; padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="580" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 12px 30px rgba(7,27,61,.12);">
  <tr><td style="background:linear-gradient(135deg,#0b2a5b,#071b3d); padding:24px 28px; color:#fff;">
    <h1 style="margin:0; font-size:20px;">💰 New Wallet Deposit Request</h1>
    <p style="margin:6px 0 0; color:#e8c16b; font-size:13px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;">{{ $d->reference() }}</p>
  </td></tr>
  <tr><td style="padding:28px; color:#0b2a5b; font-size:14px; line-height:1.6;">
    <p style="margin:0 0 18px;">Hey bro,</p>
    <p style="margin:0 0 18px;">A customer just submitted a wallet top-up request. Details below:</p>
    <table role="presentation" width="100%" cellpadding="10" cellspacing="0" style="background:#f7f9fd; border-radius:12px;">
      <tr><td style="color:#5a6983; font-weight:700; width:160px;">Customer</td><td style="font-weight:800;">{{ $d->user->name }} ({{ $d->user->email }} / {{ $d->user->phone }})</td></tr>
      <tr><td style="color:#5a6983; font-weight:700;">Amount</td><td style="font-weight:800; color:#0b2a5b; font-size:18px;">LKR {{ number_format($d->amount, 2) }}</td></tr>
      <tr><td style="color:#5a6983; font-weight:700;">Bank</td><td style="font-weight:700;">{{ $d->bank_name }}</td></tr>
      <tr><td style="color:#5a6983; font-weight:700;">Depositor Name</td><td style="font-weight:700;">{{ $d->depositor_name }}</td></tr>
      @if($d->reference_number)
      <tr><td style="color:#5a6983; font-weight:700;">Bank Reference</td><td style="font-weight:700;">{{ $d->reference_number }}</td></tr>
      @endif
      <tr><td style="color:#5a6983; font-weight:700;">Submitted</td><td style="font-weight:700;">{{ $d->created_at->format('Y-m-d H:i') }}</td></tr>
    </table>
    <p style="margin:20px 0 0; text-align:center;">
      <a href="{{ route('admin.deposits.show', $d) }}" style="display:inline-block; padding:12px 22px; background:linear-gradient(135deg,#e8a317,#d4930f); color:#2a1a00; font-weight:800; text-decoration:none; border-radius:10px;">Review in Admin Panel →</a>
    </p>
    <p style="margin:18px 0 0; font-size:12px; color:#8a94a8; text-align:center;">Slip attached to this email if upload was successful.</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
