@extends('layouts.admin')
@section('title', "Edit {$provider->name}")

@section('content')

<div class="card" style="max-width:720px;">
  <div class="card__head"><h3>Provider Settings — {{ $provider->name }}</h3></div>

  <form method="POST" action="{{ route('admin.providers.update', $provider) }}">
    @csrf
    @method('PATCH')

    <div class="form-grid">
      <div class="field">
        <label>Display Name</label>
        <input type="text" name="name" value="{{ old('name', $provider->name) }}" required>
      </div>

      <div class="field">
        <label>Country</label>
        <input type="text" value="{{ strtoupper($provider->country) }}" disabled>
      </div>

      <div class="field" style="grid-column:1/-1;">
        <label>API Base URL</label>
        <input type="url" name="base_url" value="{{ old('base_url', $provider->base_url) }}" placeholder="https://provider.example.com/api">
      </div>

      <div class="field" style="grid-column:1/-1;">
        <label>API Key</label>
        <input type="text" name="api_key" value="{{ old('api_key', $provider->api_key) }}" placeholder="Enter API key (leave blank to keep current)">
        <div class="hint">Stored in the database. The key is sent with every recharge / status / balance request.</div>
      </div>

      <div class="field field-inline">
        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $provider->is_active) ? 'checked' : '' }}>
        <label for="is_active" style="margin:0;">Provider is active</label>
      </div>
    </div>

    <div style="margin-top:22px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <button type="submit" class="btn-admin btn-admin--primary" data-loading="Saving…">Save Changes</button>
      <a href="{{ route('admin.providers.index') }}" class="btn-admin btn-admin--ghost">Cancel</a>

      <span class="spacer" style="flex:1;"></span>
    </div>
  </form>

  {{-- IMPORT is a separate form outside the save form (no nesting!) --}}
  <form method="POST" action="{{ route('admin.providers.import', $provider) }}" data-ajax style="margin-top:10px;">
    @csrf
    <button type="submit" class="btn-admin btn-admin--gold" data-loading="Importing…">Import Services Now</button>
  </form>
</div>

<p style="margin-top:18px; color:var(--muted); font-size:13px;">
  <b>Important:</b> Never reveal this provider name or API details to end customers. The customer-facing catalog only shows operator brands (Dialog, Mobitel, CEB, etc.) — no mention of upstream providers anywhere.
</p>

@endsection
