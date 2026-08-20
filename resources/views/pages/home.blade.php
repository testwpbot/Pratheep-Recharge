@extends('layouts.app')

@section('title', 'Happy Pratheep Recharge — Mobile Reloads, ISP & Utility Bill Payments in Sri Lanka')
@section('meta_description', 'Mobile reloads, data packages, ISP bill payments and utility bills for all Sri Lankan providers. Fast turnaround, secure bank transfers, 24/7 support.')

@section('content')

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="wrap">
    <div class="hero__inner">

      <div class="hero__copy">
        <span class="eyebrow"><b>Sri Lanka</b> All networks &amp; billers in one place</span>

        <h1>Recharge in <span class="grad">seconds</span>.<br>Pay every bill from <span class="ul">one screen</span>.</h1>

        <p class="lede">
          Mobile reloads for Dialog, Mobitel, Hutch and Airtel — plus broadband,
          electricity, water and TV bills. No queues, no hidden markup, handled by a team you can reach.
        </p>

        <div class="hero__actions">
          <a href="{{ route('recharge.index') }}" class="btn-slide" aria-label="Start recharging">
            <span class="btn-slide__decor"></span>
            <span class="btn-slide__content">
              <span class="btn-slide__icon">
                <svg viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <defs>
                    <linearGradient id="hpr-bolt" x1="25" y1="2" x2="25" y2="48" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#fff" stop-opacity=".71"/>
                      <stop offset="1" stop-color="#fff" stop-opacity="0"/>
                    </linearGradient>
                  </defs>
                  <circle opacity=".5" cx="25" cy="25" r="23" fill="url(#hpr-bolt)"/>
                  <path d="M28.6 5.4a1.1 1.1 0 0 1 1.9 1l-2.7 12.2h7.4a1.1 1.1 0 0 1 .85 1.82L22.4 44.7a1.1 1.1 0 0 1-1.93-1l2.7-12.2h-7.4a1.1 1.1 0 0 1-.86-1.8z" fill="#fff"/>
                </svg>
              </span>
              <span class="btn-slide__text">Start Recharging</span>
            </span>
          </a>
          <a href="{{ route('recharge.category', 'utility') }}" class="btn btn--navy btn--lg">
            <x-icon name="mail" :size="17"/>
            Pay a Bill
          </a>
        </div>

        <div class="proof">
          <div class="proof__avatars">
            <span><img src="{{ asset('assets/avatar1.png') }}" alt="Customer"></span>
            <span><img src="{{ asset('assets/avatar2.png') }}" alt="Customer"></span>
            <span><img src="{{ asset('assets/avatar3.png') }}" alt="Customer"></span>
            <span><img src="{{ asset('assets/avatar4.png') }}" alt="Customer"></span>
            <span class="more">25K+</span>
          </div>
          <div class="proof__body">
            <div class="proof__stars">
              <svg viewBox="0 0 24 24"><path d="m12 2.6 2.9 5.9 6.5.95-4.7 4.58 1.11 6.47L12 17.44 6.19 20.5 7.3 14.03 2.6 9.45l6.5-.95z"/></svg><svg viewBox="0 0 24 24"><path d="m12 2.6 2.9 5.9 6.5.95-4.7 4.58 1.11 6.47L12 17.44 6.19 20.5 7.3 14.03 2.6 9.45l6.5-.95z"/></svg><svg viewBox="0 0 24 24"><path d="m12 2.6 2.9 5.9 6.5.95-4.7 4.58 1.11 6.47L12 17.44 6.19 20.5 7.3 14.03 2.6 9.45l6.5-.95z"/></svg><svg viewBox="0 0 24 24"><path d="m12 2.6 2.9 5.9 6.5.95-4.7 4.58 1.11 6.47L12 17.44 6.19 20.5 7.3 14.03 2.6 9.45l6.5-.95z"/></svg><svg viewBox="0 0 24 24"><path d="m12 2.6 2.9 5.9 6.5.95-4.7 4.58 1.11 6.47L12 17.44 6.19 20.5 7.3 14.03 2.6 9.45l6.5-.95z"/></svg><b>4.9</b>
            </div>
            <span class="proof__text">Trusted by <b>25,000+</b> customers across Sri Lanka</span>
          </div>
        </div>
      </div>

      <div class="hero__art">
        <img src="{{ asset('assets/hero-person.webp') }}" alt="Smiling customer holding a credit card and tablet to recharge and pay bills online">
      </div>

    </div>
  </div>
</section>

<!-- ============ PROVIDERS ============ -->
<section class="providers">
  <div class="wrap">
    <p class="providers__title">Trusted across every major Sri Lankan network &amp; biller</p>
  </div>
  <div class="marquee">
    <div class="marquee__track">
      @foreach(array_merge($providers, $providers) as $p)
        <div class="chip" title="{{ $p['name'] }}">
          <img src="{{ asset('assets/logos/' . $p['logo']) }}" alt="{{ $p['name'] }} logo" loading="lazy">
          <em>{{ $p['tag'] }}</em>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- ============ SERVICES ============ -->
<section class="sec sec--light" id="services">
  <div class="wrap">
    <div class="sec__head">
      <span class="sec__eyebrow"><i></i>Our Services<i></i></span>
      <h2>Everything you pay for, <em>in one place</em></h2>
      <p class="sec__sub">From a quick reload to your monthly electricity bill — Happy Pratheep Recharge covers every major Sri Lankan network and biller, around the clock.</p>
    </div>
    <div class="cards">
      @foreach($services as $s)
        <article class="card">
          @if($s['badge'])<span class="card__badge">{{ $s['badge'] }}</span>@endif
          <span class="card__ic"><x-icon :name="$s['icon']" :size="25"/></span>
          <h3>{{ $s['title'] }}</h3>
          <p>{{ $s['desc'] }}</p>
          <div class="card__tags">
            @foreach($s['tags'] as $tag)<span>{{ $tag }}</span>@endforeach
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="sec sec--tint" id="how">
  <div class="wrap">
    <div class="sec__head">
      <span class="sec__eyebrow"><i></i>How It Works<i></i></span>
      <h2>Recharge in <em>four simple steps</em></h2>
      <p class="sec__sub">No account needed for a quick top-up. Choose, enter, pay, done — the whole thing takes less than a minute.</p>
    </div>
    <div class="steps">
      @foreach($steps as $i => $st)
        <div class="step">
          <div class="step__num"><x-icon :name="$st['icon']" :size="30"/><b>{{ $i+1 }}</b></div>
          <h3>{{ $st['title'] }}</h3>
          <p>{{ $st['desc'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- ============ WHY CHOOSE US ============ -->
<section class="sec why" id="why">
  <div class="wrap">
    <div class="sec__head">
      <span class="sec__eyebrow"><i></i>Why Choose Us<i></i></span>
      <h2>Sri Lanka's <em>easiest</em> way to top up</h2>
      <p class="sec__sub">Thousands of customers trust us every month because we keep it fast, fair and genuinely helpful.</p>
    </div>

    <div class="why__grid">
      @foreach($features as $f)
        <div class="feat">
          <span class="feat__ic"><x-icon :name="$f['icon']" :size="22"/></span>
          <div>
            <h3>{{ $f['title'] }}</h3>
            <p>{{ $f['desc'] }}</p>
          </div>
        </div>
      @endforeach
    </div>

    <div class="stats">
      @foreach($stats as $s)
        <div class="stat"><b>{{ $s['value'] }}</b><span>{{ $s['label'] }}</span></div>
      @endforeach
    </div>
  </div>
</section>

@endsection
