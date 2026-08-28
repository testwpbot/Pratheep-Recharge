@php
  /** @var array<int, array{label:string,value:string,large?:bool}> $rows */
  $rows = $rows ?? [];
@endphp
@if($rows)
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 4px;border:1px solid #e4e4e7;border-radius:6px;">
  @foreach($rows as $i => $row)
    <tr>
      <td style="padding:11px 14px;width:38%;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:13px;color:#71717a;border-top:{{ $i === 0 ? '0' : '1px solid #f4f4f5' }};vertical-align:top;">
        {{ $row['label'] }}
      </td>
      <td style="padding:11px 14px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:{{ !empty($row['large']) ? '16px' : '13px' }};font-weight:{{ !empty($row['large']) ? '600' : '500' }};color:#18181b;border-top:{{ $i === 0 ? '0' : '1px solid #f4f4f5' }};vertical-align:top;">
        {!! $row['html'] ?? e($row['value'] ?? '') !!}
      </td>
    </tr>
  @endforeach
</table>
@endif
