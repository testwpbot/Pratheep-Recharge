@extends('pages.legal')

@php
  $legalEyebrow = 'Your money';
  $legalTitle = 'Refund Policy';
  $legalIntro = 'When money goes back to your wallet, and when it does not.';
@endphp

@section('legal')
  <h3>Short version</h3>
  <p>If a recharge <b>fails for real</b>, we put the <b>same amount back in your Happy Pratheep wallet</b>. We do not send that money back to your bank as a normal step.</p>

  <h3>When we refund the wallet</h3>
  <ul>
    <li>The network rejects the order at once (wrong number, below their minimum, they have no credit, and similar).</li>
    <li>Our system hits an error before the order is sent.</li>
    <li>A pending order later comes back as failed, cancelled or refunded from the partner. We then mark the order <b>Refunded</b> and credit the wallet once.</li>
  </ul>
  <p>You can see these on <a href="{{ route('refunds') }}">Refunds</a> and on the order page.</p>

  <h3>When we do not refund yet</h3>
  <ul>
    <li><b>Pending</b> orders. We still do not know if the network sent it. Money stays on the order until they answer.</li>
    <li><b>Timeouts</b>. Same as pending. We check again every minute.</li>
    <li><b>Successful</b> orders. The reload or bill was accepted. We cannot pull it back from the phone or the biller.</li>
    <li>Admin sends the <b>same pending order</b> through another route (for example Dialog and Dialog API). You are not charged again, and we do not refund, because the first route may still complete.</li>
  </ul>

  <h3>Bank deposits</h3>
  <p>A deposit that we approve stays in your wallet for you to spend. If we reject a slip, no money is added. We do not pay rejected or unused wallet funds back to your bank account unless we agree that in writing for a special case.</p>

  <h3>Wrong number or wrong account</h3>
  <p>If you typed the number and the network already paid it, that is not a failed order. Write to <a href="{{ route('support') }}">Support</a> at once. We will try to help, but the operator often cannot reverse it.</p>

  <h3>How long it takes</h3>
  <p>An instant reject is refunded at once. A pending order is refunded when the partner says it failed — often within minutes, sometimes longer. One order is refunded only once.</p>

  <h3>Cashback</h3>
  <p>Cashback is added only on success. A refunded order does not get cashback, and we do not take cashback back because it was never added.</p>

  <h3>Ask us</h3>
  <p>If something looks wrong, open a complaint on that order or write to {{ $contact['email'] ?? 'hello@happypratheep.lk' }}. Include the order number.</p>
@endsection
