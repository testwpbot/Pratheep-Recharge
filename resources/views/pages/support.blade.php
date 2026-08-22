@extends('pages.legal')

@php
  $legalEyebrow = 'Help Centre';
  $legalTitle = 'Support';
  $legalIntro = 'Need help with a reload, bill payment or wallet deposit? We are here every day.';
@endphp

@section('legal')
  <h3>How to reach us</h3>
  <p>Use any of these. WhatsApp is usually the fastest.</p>
  <div class="legal-cards">
    <div class="legal-card">
      <b>WhatsApp</b>
      <p>Use the green chat button on this website. Write your order number if you have one (example: HPR-20260823-XXXX).</p>
    </div>
    <div class="legal-card">
      <b>Email</b>
      <p>{{ $contact['email'] ?? 'hello@happypratheep.lk' }}</p>
    </div>
    <div class="legal-card">
      <b>Phone</b>
      <p>{{ $contact['phone'] ?? '+94 77 123 4567' }}</p>
    </div>
    <div class="legal-card">
      <b>Hours</b>
      <p>{{ $contact['hours'] ?? 'Open 24 hours · 7 days' }}</p>
    </div>
  </div>

  <h3>If you already have an account</h3>
  <ul>
    <li>Open <a href="{{ route('recharge.history') }}">My Orders</a> to check a recharge.</li>
    <li>Open <a href="{{ route('wallet') }}">My Wallet</a> if a bank deposit is still pending.</li>
    <li>Open <a href="{{ route('complaints') }}">My Complaints</a> to send a ticket on a specific order.</li>
    <li>Open <a href="{{ route('refunds') }}">Refunds</a> to see money put back in your wallet.</li>
  </ul>

  <h3>Common questions</h3>
  <h3 style="font-size:16px; margin-top:16px;">My recharge is pending</h3>
  <p>We sent it to the network and are waiting for their answer. Money stays in the order until they say success or fail. If it fails, the same amount goes back to your wallet and the order shows <b>Refunded</b>.</p>

  <h3 style="font-size:16px;">I typed the wrong number</h3>
  <p>Tell us at once with the order number. If the network already sent the reload, we cannot take it back. Check the number before you tap Recharge Now.</p>

  <h3 style="font-size:16px;">My bank deposit is not in the wallet</h3>
  <p>We check the slip by hand. Use the same name and amount you typed. A clear photo of the full slip helps. Most deposits are checked within a few hours.</p>

  <h3 style="font-size:16px;">I did not get the email code</h3>
  <p>Look in spam. Wait a minute and tap resend. The code is 6 digits. Write to us if it still does not arrive.</p>

  <h3 style="font-size:16px;">Wallet is too low to recharge</h3>
  <p>You need at least LKR 100 in the wallet to start a recharge. After a recharge the leftover can go below 100. Add money again before the next one.</p>

  <p style="margin-top:22px;">Also see our <a href="{{ route('refund') }}">Refund Policy</a> and <a href="{{ route('terms') }}">Terms of Service</a>.</p>
@endsection
