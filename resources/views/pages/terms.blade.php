@extends('pages.legal')

@php
  $legalEyebrow = 'Using this site';
  $legalTitle = 'Terms of Service';
  $legalIntro = 'The rules for using Happy Pratheep Recharge. Please read them before you add money or place an order.';
@endphp

@section('legal')
  <h3>The service</h3>
  <p>Happy Pratheep Recharge lets you add money to a wallet and pay for mobile reloads, data, TV, DTH and utility bills in Sri Lanka. We send each order to the network or biller. We are not Dialog, Mobitel, Hutch, Airtel, CEB, LECO, NWSDB or those companies.</p>

  <h3>Your account</h3>
  <ul>
    <li>Give a real name, email and phone number.</li>
    <li>Keep your password private. You are responsible for orders made while you are signed in.</li>
    <li>We may ask for a 6-digit email code when you sign up or sign in from a new place.</li>
    <li>We may pause or close an account if we see fraud, abuse, or unpaid problems.</li>
  </ul>

  <h3>Wallet</h3>
  <ul>
    <li>You add money by bank transfer and upload a slip. We credit the wallet after we check the slip.</li>
    <li>The smallest first deposit is LKR 100 (or the amount shown on the wallet page).</li>
    <li>You need at least LKR 100 in the wallet to start a recharge. After a recharge the leftover can go below 100.</li>
    <li>Wallet money is not a bank account and does not earn interest.</li>
    <li>We do not send wallet money back to your bank as a normal step. See the <a href="{{ route('refund') }}">Refund Policy</a>.</li>
  </ul>

  <h3>Orders</h3>
  <ul>
    <li>Check the number, account and amount before you pay. A reload sent to the wrong number usually cannot be reversed.</li>
    <li>We take the amount from your wallet when you place the order.</li>
    <li><b>Success</b> means the network accepted it. Cashback (if any) is added then.</li>
    <li><b>Pending</b> means we are still waiting. Money stays on the order.</li>
    <li><b>Refunded</b> means it did not go through and the same amount is back in your wallet.</li>
    <li>If a request times out, we keep it pending and check again. We do not guess.</li>
  </ul>

  <h3>What we cannot promise</h3>
  <ul>
    <li>Network coverage, delays or errors on the operator side.</li>
    <li>A bill that is already overdue or cut off by the biller.</li>
    <li>Instant credit when the operator is slow or down.</li>
  </ul>

  <h3>Acceptable use</h3>
  <p>Do not use this site for crime, stolen money, fake slips, or to harm other people. We may refuse an order or freeze a wallet while we check.</p>

  <h3>Our role</h3>
  <p>We process payments you ask for. If the operator later reverses or fails a payment, we follow our refund rules. Admin may send a stuck order through another valid route for the same number and amount. You are not charged twice for that.</p>

  <h3>Changes</h3>
  <p>We may update these terms. The date at the top of this page is the latest version. Keep using the site after a change means you accept the new terms.</p>

  <h3>Sri Lanka law</h3>
  <p>These terms follow the laws of Sri Lanka. If a part cannot be enforced, the rest still applies.</p>

  <h3>Contact</h3>
  <p><a href="{{ route('support') }}">Support</a> · {{ $contact['email'] ?? 'hello@happypratheep.lk' }}</p>
@endsection
