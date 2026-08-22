@extends('emails.layout')

@section('title', 'Reset your password')
@section('preheader', 'Use this link to set a new Happy Pratheep password. It expires in 60 minutes.')
@section('heading', 'Reset your password')

@section('content')
  <p style="margin:0 0 14px;">Hi {{ explode(' ', trim($user->name))[0] }},</p>
  <p style="margin:0 0 16px;">Someone asked to reset the password for this Happy Pratheep account. Tap the button to choose a new one.</p>
@endsection

@section('action')
  @include('emails.partials.button', ['url' => $resetUrl, 'label' => 'Set a new password'])
  <p style="margin:16px 0 0;font-size:13px;color:#71717a;">
    This link expires in 60 minutes. If you did not ask for a reset, you can ignore this email. Your old password still works.
  </p>
@endsection

@section('footer')
  You received this because a password reset was requested for this email on Happy Pratheep Recharge.
@endsection
