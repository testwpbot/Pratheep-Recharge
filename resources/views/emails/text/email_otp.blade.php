Your verification code

Hi {{ explode(' ', trim($user->name))[0] }},

@if($purpose === 'signup')
Use this code to confirm your email and finish creating your account.
@else
We noticed a sign-in from a new location. Enter this code to continue.
@endif

Code: {{ $code }}

This code expires in 10 minutes.
@if($ip)
Sign-in IP: {{ $ip }}
@endif

If you did not try to sign in, you can ignore this email.

Happy Pratheep Recharge
