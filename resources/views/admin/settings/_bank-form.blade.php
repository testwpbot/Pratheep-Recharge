@php
  /** @var \App\Models\BankAccount|null $account */
  $account = $account ?? null;
  $slug = old('bank_slug', $account->bank_slug ?? 'bank-of-ceylon');
  $isCustom = $slug === 'custom';
  $action = $account
      ? route('admin.settings.banks.update', $account)
      : route('admin.settings.banks.store');
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="bank-form">
  @csrf
  @if($account) @method('PATCH') @endif

  <div class="form-grid">
    <div class="field" style="grid-column:1/-1;">
      <label>Bank <span class="req">*</span></label>
      <div class="hpr-dd" data-hpr-dd data-bank-picker>
        <input type="hidden" name="bank_slug" value="{{ $slug }}">
        <button type="button" class="hpr-dd__btn">
          <span class="hpr-dd__label">
            @php $cur = \App\Support\SriLankanBanks::find($slug); @endphp
            @if($cur && $cur['logo'])
              <span class="bank-dd-preview"><img src="{{ asset($cur['logo']) }}" alt=""> {{ $cur['name'] }}</span>
            @else
              {{ $cur['name'] ?? 'Pick a bank' }}
            @endif
          </span>
          <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
        <div class="hpr-dd__menu" hidden>
          @foreach ($bankCatalog as $b)
            <button type="button" class="hpr-dd__item {{ $slug===$b['slug'] ? 'is-active' : '' }}"
                    data-value="{{ $b['slug'] }}"
                    data-label="{{ $b['name'] }}"
                    data-bank-name="{{ $b['name'] }}">
              <span data-dd-preview class="bank-dd-preview">
                @if($b['logo'])
                  <img src="{{ asset($b['logo']) }}" alt="">
                @else
                  <span class="bank-dd-fallback">{{ strtoupper(substr($b['name'],0,2)) }}</span>
                @endif
                {{ $b['name'] }}
              </span>
            </button>
          @endforeach
        </div>
      </div>
    </div>

    <div class="field bank-custom-name" @unless($isCustom) style="display:none" @endunless>
      <label>Bank name <span class="req">*</span></label>
      <input type="text" name="bank_name" value="{{ old('bank_name', $account->bank_name ?? optional(\App\Support\SriLankanBanks::find($slug))['name'] ?? '') }}" placeholder="Type the bank name">
    </div>
    @unless($isCustom)
      <input type="hidden" class="bank-name-hidden" name="bank_name" value="{{ old('bank_name', $account->bank_name ?? optional(\App\Support\SriLankanBanks::find($slug))['name'] ?? '') }}">
    @endunless

    <div class="field">
      <label>Account name <span class="req">*</span></label>
      <input type="text" name="account_name" value="{{ old('account_name', $account->account_name ?? '') }}" required>
    </div>
    <div class="field">
      <label>Account number <span class="req">*</span></label>
      <input type="text" name="account_no" value="{{ old('account_no', $account->account_no ?? '') }}" required>
    </div>
    <div class="field">
      <label>Branch</label>
      <input type="text" name="branch" value="{{ old('branch', $account->branch ?? '') }}">
    </div>
    <div class="field bank-custom-logo" @unless($isCustom) style="display:none" @endunless>
      <label>Logo URL</label>
      <input type="url" name="logo_url" value="{{ old('logo_url', $account->logo_url ?? '') }}" placeholder="https://…">
    </div>
    <div class="field bank-custom-logo" @unless($isCustom) style="display:none" @endunless>
      <label>Or upload logo</label>
      <input type="file" name="logo" accept="image/*">
    </div>
    <div class="field" style="grid-column:1/-1;">
      <label>Note for customers</label>
      <textarea name="instructions" rows="2">{{ old('instructions', $account->instructions ?? '') }}</textarea>
    </div>
    @if($account)
      <div class="field" style="display:flex; align-items:center; gap:10px; padding-top:22px;">
        <label class="sw">
          <input type="checkbox" name="is_active" value="1" @checked($account->is_active)>
          <span class="sw__slider"></span>
        </label>
        <span style="font-weight:700; color:var(--navy-800);">Show this account to customers</span>
      </div>
    @endif
  </div>
  <div style="margin-top:16px; display:flex; justify-content:flex-end; gap:8px;">
    <button type="submit" class="btn-admin btn-admin--gold">
      <span class="btn-label">{{ $account ? 'Save account' : 'Add bank account' }}</span>
      <span class="btn-spinner" hidden></span>
    </button>
  </div>
</form>
