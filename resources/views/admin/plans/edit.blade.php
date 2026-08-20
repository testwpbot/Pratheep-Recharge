@extends('layouts.admin')
@section('title', ($plan->exists ? 'Edit' : 'Add') . ' Plan')

@section('content')

<div class="card" style="max-width:780px;">
  <div class="card__head">
    <h3>{{ $plan->exists ? 'Edit Plan' : 'Add New Plan' }}</h3>
    <a href="{{ route('admin.plans.index') }}" class="btn-admin btn-admin--ghost btn-admin--sm">← Back to plans</a>
  </div>

  <form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}">
    @csrf
    @if ($plan->exists) @method('PATCH') @endif

    <div class="form-grid">
      <div class="field" style="grid-column:1/-1;">
        <label>Service / Operator <span class="req">*</span></label>
        <select name="service_id" required>
          <option value="">— Select service —</option>
          @foreach ($services as $s)
            <option value="{{ $s->id }}" {{ old('service_id', $plan->service_id) == $s->id ? 'selected' : '' }}>
              {{ $s->name }} ({{ ucfirst($s->type) }})
            </option>
          @endforeach
        </select>
        @error('service_id')<div class="hint" style="color:#b42f2f;">{{ $message }}</div>@enderror
      </div>

      <div class="field" style="grid-column: span 2;">
        <label>Plan Name <span class="req">*</span></label>
        <input type="text" name="name" value="{{ old('name', $plan->name) }}" placeholder="e.g. Dialog 200 Reload or 1.5GB Anytime Data" required>
      </div>

      <div class="field">
        <label>Amount (LKR) <span class="req">*</span></label>
        <input type="number" step="0.01" min="10" name="amount" value="{{ old('amount', $plan->amount) }}" required>
      </div>

      <div class="field">
        <label>Type</label>
        <select name="type">
          @foreach ([
            'reload'   => 'Reload / Talk-time',
            'data'     => 'Data Package',
            'voice'    => 'Voice / Minutes',
            'combo'    => 'Combo / Blaster',
            'social'   => 'Social Media Pack',
            'tv'       => 'TV Subscription',
            'postpaid' => 'Postpaid Monthly Pack',
            'bill'     => 'Bill / Wallet / Insurance',
            'utility'  => 'Utility Payment',
          ] as $k => $v)
            <option value="{{ $k }}" {{ old('type', $plan->type) === $k ? 'selected' : '' }}>{{ $v }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label>Plan Code (optional)</label>
        <input type="text" name="plan_code" value="{{ old('plan_code', $plan->plan_code) }}" placeholder="Provider-specific code">
      </div>

      <div class="field">
        <label>Validity</label>
        <input type="text" name="validity" value="{{ old('validity', $plan->validity) }}" placeholder="e.g. 7 Days, 30 Days, No expiry">
      </div>

      <div class="field">
        <label>Sort Order</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}">
      </div>

      <div class="field" style="grid-column:1/-1;">
        <label>Description (what the customer gets)</label>
        <textarea name="description" rows="3" placeholder="e.g. 1.5GB Anytime Data + 200 D2D mins + 200 SMS, valid 30 days">{{ old('description', $plan->description) }}</textarea>
      </div>

      <div class="field field-inline" style="grid-column:1/-1;">
        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
        <label for="is_active" style="margin:0;">Active (visible to customers)</label>
      </div>
    </div>

    <div style="margin-top:22px; display:flex; gap:10px;">
      <button type="submit" class="btn-admin btn-admin--gold" data-loading="Saving…">
        {{ $plan->exists ? 'Save Changes' : 'Create Plan' }}
      </button>
      <a href="{{ route('admin.plans.index') }}" class="btn-admin btn-admin--ghost">Cancel</a>
    </div>
  </form>
</div>

@endsection
