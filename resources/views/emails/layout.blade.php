@php
  $appName = config('app.name', 'Happy Pratheep Recharge');
  $homeUrl = rtrim((string) config('app.url'), '/') ?: url('/');
  $support = \App\Models\Setting::get('general', 'support_email');
  $preheader = trim($__env->yieldContent('preheader'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <title>@yield('title', $appName)</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
  @if($preheader !== '')
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f4f4f5;opacity:0;">
    {{ $preheader }}
  </div>
  @endif

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f4f5;margin:0;padding:0;">
    <tr>
      <td align="center" style="padding:32px 16px 40px;">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:560px;">

          {{-- Wordmark --}}
          <tr>
            <td style="padding:0 4px 20px;">
              <a href="{{ $homeUrl }}" style="text-decoration:none;color:#0b2a5b;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:14px;font-weight:600;letter-spacing:-0.01em;">
                Happy Pratheep
              </a>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border:1px solid #e4e4e7;border-radius:8px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="padding:32px 36px 8px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;">
                    @hasSection('eyebrow')
                      <p style="margin:0 0 8px;font-size:12px;line-height:1.4;color:#71717a;font-weight:500;">
                        @yield('eyebrow')
                      </p>
                    @endif
                    <h1 style="margin:0;font-size:20px;line-height:1.35;font-weight:600;color:#18181b;letter-spacing:-0.02em;">
                      @yield('heading')
                    </h1>
                  </td>
                </tr>
                <tr>
                  <td style="padding:16px 36px 8px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.65;color:#3f3f46;">
                    @yield('content')
                  </td>
                </tr>
                @hasSection('action')
                <tr>
                  <td style="padding:20px 36px 8px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;">
                    @yield('action')
                  </td>
                </tr>
                @endif
                <tr>
                  <td style="padding:28px 36px 32px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6;color:#71717a;">
                    @yield('signoff')
                    @hasSection('signoff') @else
                      Happy Pratheep Recharge
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="padding:20px 4px 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:#a1a1aa;">
              @hasSection('footer')
                @yield('footer')
              @else
                You received this email because of an account activity on {{ $appName }}.
              @endif
              <br>
              @if($support)
                Questions? Write to <a href="mailto:{{ $support }}" style="color:#71717a;text-decoration:underline;">{{ $support }}</a>
                <br>
              @endif
              <a href="{{ $homeUrl }}" style="color:#a1a1aa;text-decoration:none;">{{ parse_url($homeUrl, PHP_URL_HOST) ?: $homeUrl }}</a>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
