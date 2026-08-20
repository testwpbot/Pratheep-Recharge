@extends('layouts.app')
@section('title', 'Services — ' . config('app.name'))

@section('content')

<section class="sec sec--light">
  <div class="wrap">
    <div class="sec__head">
      <span class="sec__eyebrow"><i></i>Our Services<i></i></span>
      <h2>Everything you pay for, <em>in one place</em></h2>
      <p class="sec__sub">Pick a category, choose your operator, enter the number and amount — done. Earn cashback on every successful payment.</p>
    </div>

    <div class="cat-tabs">
      @foreach ($categories as $cat)
        <a href="{{ route('recharge.category', $cat->slug) }}" class="{{ $activeCategory?->slug === $cat->slug ? 'active' : '' }}">
          {{ $cat->name }}
        </a>
      @endforeach
    </div>

    @if ($activeCategory)
      <h3 style="font-size:20px; font-weight:800; color:var(--navy-900); margin:0 0 18px;">
        {{ $activeCategory->name }}
      </h3>
    @endif

    @if ($services->isEmpty())
      <div class="card" style="text-align:center; padding:40px;">
        <p style="color:var(--muted); margin:0;">No services available in this category yet. Please check back soon.</p>
      </div>
    @else
      <div class="service-grid">
        @foreach ($services as $s)
          <a href="{{ route('recharge.form', $s) }}" class="service-card">
            @if ((float) $s->profit > 0)
              <span class="cb-badge">
                @if ($s->profit_type === 'PCT') {{ number_format($s->profit, 2) }}% cashback
                @else LKR {{ number_format($s->profit, 2) }} cashback @endif
              </span>
            @endif
            <img src="{{ $s->logo ? asset($s->logo) : asset('assets/logo-mark.png') }}" alt="{{ $s->name }}">
            <h4>{{ $s->name }}</h4>
            <small>{{ ucfirst($s->type) }}</small>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>

@endsection
