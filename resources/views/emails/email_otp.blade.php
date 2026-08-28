@extends('emails.layout')

@section('title', 'Your verification code')
@section('preheader', 'Your Happy Pratheep code is ' . $code . '. It expires in 10 minutes.')
@section('heading', 'Your verification code')

@section('content')
  <p style="margin:0 0 14px;">Hi {{ explode(' ', trim($user->name))[0] }},</p>
  @if($purpose === 'signup')
    <p style="margin:0 0 16px;">Use this code to confirm your email and finish creating your account.</p>
  @else
    <p style="margin:0 0 16px;">We noticed a sign-in from a new location. Enter this code to continue.</p>
  @endif

  <p style="margin:20px 0;text-align:center;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:32px;letter-spacing:8px;font-weight:600;color:#18181b;">
    {{ $code }}
  </p>

  <p style="margin:0;font-size:13px;color:#71717a;">
    This code expires in 10 minutes.
    @if($ip)
      Sign-in IP: {{ $ip }}.
    @endif
    If you did not try to sign in, you can ignore this email and keep your password as it is.
  </p>
@endsection

@section('footer')
  You received this because someone used your email on Happy Pratheep Recharge.
@endsection
