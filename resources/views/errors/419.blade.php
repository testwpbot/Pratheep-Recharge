<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session expired — {{ config('app.name') }}</title>
<style>
  body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f4f7fc;color:#0b2a5b;
       min-height:100vh;display:grid;place-items:center;padding:24px;}
  .box{background:#fff;max-width:440px;width:100%;border-radius:18px;padding:34px 30px;text-align:center;
       box-shadow:0 18px 50px rgba(11,42,91,.12);}
  .ic{width:64px;height:64px;border-radius:50%;background:#fff4e0;color:#c07c00;display:grid;place-items:center;
      margin:0 auto 18px;font-size:30px;}
  h1{font-size:21px;margin:0 0 10px;font-weight:800;}
  p{font-size:14.5px;line-height:1.6;color:#4a5b78;font-weight:500;margin:0 0 22px;}
  .btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
  a.btn{display:inline-block;padding:11px 20px;border-radius:10px;font-weight:700;font-size:14px;text-decoration:none;}
  .btn--primary{background:#d4a017;color:#1a1300;}
  .btn--ghost{background:#eef2fa;color:#0b2a5b;}
  small{display:block;margin-top:18px;color:#8494ad;font-size:12.5px;}
</style>
</head>
<body>
  <div class="box">
    <div class="ic">&#8635;</div>
    <h1>Your session timed out</h1>
    <p>
      For your security, the page was open too long and your session expired.
      Nothing was charged. Please go back and try your recharge again — you may
      need to sign in if prompted.
    </p>
    <div class="btns">
      <a class="btn btn--primary" href="{{ url()->previous() }}">Go back &amp; try again</a>
      <a class="btn btn--ghost" href="{{ route('dashboard') }}">Go to dashboard</a>
    </div>
    <small>If this keeps happening, sign out and sign back in.</small>
  </div>
</body>
</html>
