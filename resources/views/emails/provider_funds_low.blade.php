<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Provider funds low</title></head>
<body style="margin:0; padding:0; background:#f6f8fc; font-family:Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fc; padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="580" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 12px 30px rgba(7,27,61,.12);">
  <tr><td style="background:linear-gradient(135deg,#0b2a5b,#071b3d); padding:24px 28px; color:#fff;">
    <h1 style="margin:0; font-size:20px;">⚠ Provider funds are below customer wallets</h1>
    <p style="margin:6px 0 0; color:#e8c16b; font-size:13px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;">Happy Pratheep Recharge</p>
  </td></tr>
  <tr><td style="padding:28px; color:#0b2a5b; font-size:14px; line-height:1.6;">
    <p style="margin:0 0 16px;">Hey bro,</p>
    <p style="margin:0 0 16px;">An API provider wallet is lower than the money customers currently hold on the site. Top it up now so recharges don't fail.</p>

    <table role="presentation" width="100%" cellpadding="10" cellspacing="0" style="background:#f7f9fd; border-radius:12px; margin-bottom:16px;">
      <tr>
        <td style="color:#5a6983; font-weight:700; width:200px;">Customer wallets</td>
        <td style="font-weight:800; font-size:18px;">LKR {{ number_format($h['user_total'] ?? 0, 2) }}</td>
      </tr>
      <tr>
        <td style="color:#5a6983; font-weight:700;">LKR provider float</td>
        <td style="font-weight:800;">
          @if(($h['combined_lkr'] ?? null) === null)
            Unavailable
          @else
            LKR {{ number_format($h['combined_lkr'], 2) }}
          @endif
        </td>
      </tr>
    </table>

    @foreach (($h['pay'] ?? []) as $p)
      <div style="margin:0 0 12px; padding:14px 16px; border-radius:12px; background:#fff6e5; border:1px solid rgba(232,163,23,.45);">
        <div style="font-size:12px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#8a5a00; margin-bottom:4px;">Pay this now</div>
        <div style="font-size:20px; font-weight:800; color:#0b2a5b;">{{ $p['currency'] }} {{ number_format($p['amount'], 2) }}</div>
        <div style="font-weight:700; color:#5a6983; margin-top:4px;">to {{ $p['provider'] }}</div>
        <div style="margin-top:8px; font-size:13px; color:#0b2a5b;">{{ $p['reason'] }}</div>
      </div>
    @endforeach

    <table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="margin-top:8px;">
      <tr>
        <td style="font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#8a94a8;">Provider</td>
        <td style="font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#8a94a8;">Balance</td>
        <td style="font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#8a94a8;">Status</td>
      </tr>
      @foreach (($h['providers'] ?? []) as $r)
        <tr>
          <td style="font-weight:700;">{{ $r['name'] }}</td>
          <td style="font-weight:700;">
            @if($r['balance'] === null)
              {{ $r['error_label'] ?? 'Unavailable' }}
            @else
              {{ $r['currency'] }} {{ number_format($r['balance'], 2) }}
            @endif
          </td>
          <td style="font-weight:800; color:{{ ($r['status'] ?? '') === 'low' ? '#a52222' : (($r['status'] ?? '') === 'healthy' ? '#15733f' : '#8a5a00') }};">
            {{ ucfirst($r['status'] ?? 'unknown') }}
          </td>
        </tr>
      @endforeach
    </table>

    <p style="margin:22px 0 0; text-align:center;">
      <a href="{{ route('admin.funds.index') }}" style="display:inline-block; padding:12px 22px; background:linear-gradient(135deg,#e8a317,#d4930f); color:#2a1a00; font-weight:800; text-decoration:none; border-radius:10px;">Open Funds Health →</a>
    </p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
