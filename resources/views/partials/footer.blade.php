<!-- ============ FOOTER ============ -->
<footer class="footer">
  <div class="wrap">
    <div class="footer__top">

      <div class="footer__about">
        <a href="{{ route('home') }}" class="brand">
          <img class="brand__mark" src="{{ asset('assets/logo-mark.png') }}" alt="">
          <span class="brand__text">
            <span class="brand__l1"><span class="happy">Happy</span><span class="pratheep">PRATHEEP</span></span>
            <span class="brand__l2"><i></i><span>Recharge</span><i></i></span>
          </span>
        </a>
        <p>Your trusted Sri Lankan recharge store for mobile reloads, data packages, broadband, electricity, water and TV bills — quick, reliable service, 24 hours a day.</p>
        <div class="social">
          <a href="#" class="s-fb" aria-label="Facebook">
            <svg viewBox="0 0 24 24"><path d="M13.5 22v-8h2.7l.4-3.1h-3.1V8.9c0-.9.25-1.5 1.55-1.5h1.65V4.6A22 22 0 0 0 14.3 4.5c-2.4 0-4 1.45-4 4.1v2.3H7.6V14h2.7v8z"/></svg>
          </a>
          <a href="#" class="s-wa" aria-label="WhatsApp">
            <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2m5.1 14c-.2.6-1.2 1.2-1.7 1.2s-1.2.2-3.6-.9c-3-1.3-4.8-4.4-5-4.6s-1.2-1.6-1.2-3 .8-2.1 1-2.4a1 1 0 0 1 .8-.3h.6c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .6l-.4.5-.3.4c-.1.1-.3.3-.1.6a9 9 0 0 0 1.6 2 8 8 0 0 0 2.3 1.4c.3.2.5.1.6 0l1-1.2c.2-.2.4-.2.6-.1l2 1c.3.1.5.2.5.3z"/></svg>
          </a>
          <a href="#" class="s-ig" aria-label="Instagram">
            <svg viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2 0 1.8.3 2.2.4.6.2 1 .5 1.4 1s.8.8 1 1.4c.1.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c0 1.2-.3 1.8-.4 2.2-.2.6-.5 1-1 1.4s-.8.8-1.4 1c-.4.1-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2 0-1.8-.3-2.2-.4-.6-.2-1-.5-1.4-1s-.8-.8-1-1.4c-.1-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c0-1.2.3-1.8.4-2.2.2-.6.5-1 1-1.4s.8-.8 1.4-1c.4-.1 1-.4 2.2-.4 1.3-.1 1.7-.1 4.9-.1zm0 3.8a6 6 0 1 0 0 12 6 6 0 0 0 0-12m0 9.9A3.9 3.9 0 1 1 15.9 12 3.9 3.9 0 0 1 12 15.9m7.6-10.1a1.4 1.4 0 1 1-1.4-1.4 1.4 1.4 0 0 1 1.4 1.4"/></svg>
          </a>
        </div>
      </div>

      <div class="footer__col">
        <h4>Services</h4>
        <ul>
          <li><a href="{{ route('recharge.category', 'mobile') }}">Mobile Reload</a></li>
          <li><a href="{{ route('recharge.category', 'mobile') }}">Data Packages</a></li>
          <li><a href="{{ route('recharge.category', 'mobile') }}">Postpaid Bills</a></li>
          <li><a href="{{ route('recharge.category', 'broadband') }}">Broadband &amp; ISP</a></li>
          <li><a href="{{ route('recharge.category', 'tv') }}">TV &amp; Streaming</a></li>
          <li><a href="{{ route('gift-cards') }}">Gift Cards</a></li>
        </ul>
      </div>

      <div class="footer__col">
        <h4>Utility Bills</h4>
        <ul>
          <li><a href="{{ route('recharge.category', 'utility') }}">Electricity — CEB</a></li>
          <li><a href="{{ route('recharge.category', 'utility') }}">Electricity — LECO</a></li>
          <li><a href="{{ route('recharge.category', 'utility') }}">Water — NWSDB</a></li>
          <li><a href="{{ route('home') }}#how">How It Works</a></li>
          <li><a href="{{ route('home') }}#why">Why Choose Us</a></li>
          <li><a href="{{ route('support') }}">Help Centre</a></li>
        </ul>
      </div>

      <div class="footer__col footer__col--contact">
        <h4>Get In Touch</h4>
        <ul class="footer__contact">
          <li><x-icon name="phone"/><span>{{ $contact['phone'] ?? '+94 77 123 4567' }}</span></li>
          <li><x-icon name="mail"/><span>{{ $contact['email'] ?? 'hello@happypratheep.lk' }}</span></li>
          <li><x-icon name="pin"/><span>{!! nl2br(e($contact['address'] ?? 'Main Street, Negombo, Western Province, Sri Lanka')) !!}</span></li>
          <li><x-icon name="clock"/><span>{{ $contact['hours'] ?? 'Open 24 hours · 7 days' }}</span></li>
        </ul>
      </div>

    </div>

    <div class="footer__bottom">
      <span>&copy; <span id="year">{{ date('Y') }}</span> Happy Pratheep Recharge. All rights reserved.</span>
      <nav>
        <a href="{{ route('privacy') }}">Privacy Policy</a>
        <a href="{{ route('terms') }}">Terms of Service</a>
        <a href="{{ route('refund') }}">Refund Policy</a>
      </nav>
      <span class="made">Made in <b>Sri Lanka</b> 🇱🇰</span>
    </div>
  </div>
</footer>
