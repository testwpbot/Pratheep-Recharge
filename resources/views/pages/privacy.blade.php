@extends('pages.legal')

@php
  $legalEyebrow = 'Your data';
  $legalTitle = 'Privacy Policy';
  $legalIntro = 'How Happy Pratheep Recharge collects, uses and looks after your information.';
@endphp

@section('legal')
  <h3>Who we are</h3>
  <p>Happy Pratheep Recharge (“we”, “us”) is a Sri Lanka recharge and bill-pay service. This page covers the website and your customer account.</p>

  <h3>What we collect</h3>
  <ul>
    <li>Name, email, phone number and password when you create an account.</li>
    <li>Wallet deposits: amount, bank name, depositor name, slip photo and any reference you type.</li>
    <li>Recharge orders: service, number or account, amount, and status.</li>
    <li>Complaints you send us.</li>
    <li>Login details such as time and IP address, so we can send an email code on a new device.</li>
    <li>Basic site logs (pages opened, errors) to keep the service running.</li>
  </ul>

  <h3>What we do not collect</h3>
  <ul>
    <li>We do not store your bank password or card PIN.</li>
    <li>We do not sell your list of numbers or orders to advertisers.</li>
  </ul>

  <h3>How we use it</h3>
  <ul>
    <li>To create your account and keep you signed in.</li>
    <li>To send the email code, receipts, deposit updates and low-wallet notices.</li>
    <li>To process reloads and bills through our payment partners.</li>
    <li>To check bank slips and credit your wallet.</li>
    <li>To answer support and stop fraud or abuse.</li>
  </ul>

  <h3>Who else can see it</h3>
  <p>We only share what is needed:</p>
  <ul>
    <li>The mobile or bill network, so they can complete your order (number / account and amount).</li>
    <li>Our hosting and email providers, so the website and mail can run.</li>
    <li>The law, if a court or regulator requires it.</li>
  </ul>
  <p>Staff who handle deposits, orders and complaints can see your account to do that job.</p>

  <h3>How long we keep it</h3>
  <p>We keep account, order and wallet records while you use the service and for a reasonable time after, so we can show history, handle disputes and meet bookkeeping rules. You can ask us to close an account.</p>

  <h3>Cookies</h3>
  <p>We use a small login cookie so you stay signed in. We do not use advertising trackers on this site.</p>

  <h3>Your choices</h3>
  <ul>
    <li>Update your name, email or phone from your account (or ask us).</li>
    <li>Ask for a copy of your order history.</li>
    <li>Ask us to close the account. We may keep some records if the law or an open dispute needs them.</li>
  </ul>

  <h3>Children</h3>
  <p>This service is for people 18 and over, or younger people using it with a parent or guardian.</p>

  <h3>Contact</h3>
  <p>Privacy questions: {{ $contact['email'] ?? 'hello@happypratheep.lk' }} or the <a href="{{ route('support') }}">Support</a> page.</p>
@endsection
