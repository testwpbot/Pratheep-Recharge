@php
  $url = $url ?? '#';
  $label = $label ?? 'Continue';
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="left" bgcolor="#0b2a5b" style="background:#0b2a5b;border-radius:6px;">
      <a href="{{ $url }}"
         style="display:inline-block;padding:11px 18px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.2;font-weight:600;color:#ffffff;text-decoration:none;">
        {{ $label }}
      </a>
    </td>
  </tr>
</table>
<p style="margin:10px 0 0;font-size:12px;line-height:1.5;color:#a1a1aa;word-break:break-all;">
  Or open: <a href="{{ $url }}" style="color:#71717a;text-decoration:underline;">{{ $url }}</a>
</p>
