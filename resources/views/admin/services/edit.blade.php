@extends('layouts.admin')
@section('title', "Edit {$service->name}")

@section('content')

<div class="card" style="max-width:720px;">
  <div class="card__head">
    <h3>Edit Service — {{ $service->name }}</h3>
    <span class="pill pill--{{ $service->is_active ? 'success' : 'failed' }}">
      {{ $service->is_active ? 'Active' : 'Inactive' }}
    </span>
  </div>

  <form method="POST" action="{{ route('admin.services.update', $service) }}">
    @csrf
    @method('PATCH')

    <div class="form-grid">
      <div class="field" style="grid-column:1/-1;">
        <label>Service name</label>
        <input type="text" name="name" value="{{ old('name', $service->name) }}" required>
      </div>

      <div class="field">
        <label>Operator code</label>
        <input type="text" name="op_code" value="{{ old('op_code', $service->op_code) }}" required maxlength="20">
        <div class="hint">Sent to the provider as the operator code. Change this only if the provider gave you a different number.</div>
      </div>

      <div class="field">
        <label>Provider</label>
        <select disabled>
          @foreach ($providers as $p)
            <option {{ $service->provider_id === $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ strtoupper($p->country) }})</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label>Category</label>
        <select name="category_id">
          <option value="">— Uncategorised —</option>
          @foreach ($categories as $c)
            <option value="{{ $c->id }}" {{ (string) old('category_id', $service->category_id) === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label>Type</label>
        <select name="type">
          @foreach (['prepaid','postpaid','broadband','utility','tv','insurance','dth','wallet','api'] as $t)
            <option value="{{ $t }}" {{ old('type', $service->type) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label>Logo path (relative to public/)</label>
        <input type="text" name="logo" value="{{ old('logo', $service->logo) }}" placeholder="assets/logos/dialog.png">
        <div class="hint">Leave blank for a default initial-based badge.</div>
      </div>

      <div class="field">
        <label>Profit mode</label>
        <select name="profit_type" id="profitType">
          <option value="FLAT" {{ old('profit_type', $service->profit_type) === 'FLAT' ? 'selected' : '' }}>Fixed LKR amount</option>
          <option value="PCT"  {{ old('profit_type', $service->profit_type) === 'PCT'  ? 'selected' : '' }}>Percentage of order amount</option>
        </select>
      </div>

      <div class="field">
        <label>Profit / Cashback / Fee</label>
        <input type="number" step="0.01" name="profit" id="profitInput" value="{{ old('profit', $service->profit) }}" required>
        @error('profit')<div class="hint" style="color:var(--danger,#c0392b);">{{ $message }}</div>@enderror
        <div class="hint" id="profitHint">
          <b>Positive</b> = cashback credited to the customer on success.
          <b>Negative</b> = a customer fee (surcharge) added on top of the bill amount — only allowed for bill-type services (utility, postpaid, insurance, wallet).
        </div>
        <div class="hint" id="feeNote" style="color:var(--gold-600, #a67c00); font-weight:700; display:none;">
          ↳ This service will CHARGE the customer extra. Example: a LKR 5,000 bill with fee <span id="feeExample"></span> means the customer pays <span id="feeTotal"></span> and the provider still receives LKR 5,000.
        </div>
      </div>

      <div class="field field-inline" style="grid-column:1/-1;">
        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}>
        <label for="is_active" style="margin:0;">Service is on (customers see it only if this provider is also On)</label>
      </div>
    </div>

    @if ($service->logoUrl)
      <div style="margin-top:18px; display:flex; align-items:center; gap:12px; padding:14px; background:#f7f9fd; border-radius:12px;">
        <img src="{{ $service->logoUrl }}" alt="" style="height:44px;">
        <span style="font-size:13px; color:var(--muted);">Current operator logo preview</span>
      </div>
    @endif

    <div style="margin-top:22px; display:flex; gap:10px;">
      <button type="submit" class="btn-admin btn-admin--primary" data-loading="Saving…">Save Service</button>
      <a href="{{ route('admin.services.index') }}" class="btn-admin btn-admin--ghost">Cancel</a>
    </div>
  </form>
</div>

<script>
(function(){
  var typeSel = document.getElementById('profitType');
  var input   = document.getElementById('profitInput');
  var feeNote = document.getElementById('feeNote');
  var feeEx   = document.getElementById('feeExample');
  var feeTot  = document.getElementById('feeTotal');

  function money(n){ return 'LKR ' + Number(n).toLocaleString('en-LK', {minimumFractionDigits:2, maximumFractionDigits:2}); }

  function update(){
    var val = parseFloat(input.value || '0');
    if (val < 0){
      var isPct = typeSel.value === 'PCT';
      var feeOnBill = isPct ? (5000 * Math.abs(val) / 100) : Math.abs(val);
      feeEx.textContent = isPct ? (Math.abs(val) + '% = ' + money(feeOnBill)) : money(feeOnBill);
      feeTot.textContent = money(5000 + feeOnBill);
      feeNote.style.display = '';
    } else {
      feeNote.style.display = 'none';
    }
  }

  typeSel?.addEventListener('change', update);
  input?.addEventListener('input', update);
  update();
})();
</script>

@endsection
