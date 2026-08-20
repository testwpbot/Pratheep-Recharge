@php
  $typeColors = [
    'reload'   => 'pending',
    'data'     => 'processing',
    'voice'    => 'success',
    'combo'    => 'processing',
    'social'   => 'refunded',
    'tv'       => 'refunded',
    'bill'     => 'pending',
    'postpaid' => 'failed',
    'utility'  => 'pending',
  ];
  $typeLabels = [
    'reload'   => 'Reload',
    'data'     => 'Data',
    'voice'    => 'Voice',
    'combo'    => 'Combo',
    'social'   => 'Social',
    'tv'       => 'TV',
    'bill'     => 'Bill',
    'utility'  => 'Utility',
    'postpaid' => 'Postpaid',
  ];
  $planJson = json_encode([
    'id'           => $p->id,
    'service_id'   => $p->service_id,
    'service_name' => $p->service->name ?? '',
    'service_logo' => $p->service ? $p->service->logoUrl : asset('assets/logo-mark.png'),
    'name'         => $p->name,
    'amount'       => (float) $p->amount,
    'type'         => $p->type,
    'type_label'   => $typeLabels[$p->type] ?? ucfirst($p->type),
    'type_color'   => $typeColors[$p->type] ?? 'pending',
    'validity'     => $p->validity,
    'plan_code'    => $p->plan_code,
    'description'  => $p->description,
    'sort_order'   => (int) $p->sort_order,
    'is_active'    => (bool) $p->is_active,
    'details'      => $p->meta['details'] ?? [],
    'edit_url'     => route('admin.plans.update', $p),
    'toggle_url'   => route('admin.plans.toggle', $p),
    'delete_url'   => route('admin.plans.destroy', $p),
  ], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
@endphp
<tr id="plan-row-{{ $p->id }}"
    data-plan-row
    data-service="{{ $p->service_id }}"
    data-type="{{ $p->type }}"
    data-active="{{ $p->is_active ? 1 : 0 }}"
    data-search="{{ strtolower($p->name.' '.($p->plan_code??'').' '.($p->service->name??'').' '.$p->amount) }}">
  <td>
    <div class="op-cell">
      <img src="{{ $p->service ? $p->service->logoUrl : asset('assets/logo-mark.png') }}" alt="" onerror="this.style.display='none'">
      <b>{{ $p->service->name ?? '—' }}</b>
    </div>
  </td>
  <td>
    <b>{{ $p->name }}</b>
    @if ($p->description)<br><small style="color:var(--muted);">{{ $p->description }}</small>@endif
  </td>
  <td>
    <span class="pill pill--{{ $typeColors[$p->type] ?? 'pending' }}">{{ $typeLabels[$p->type] ?? ucfirst($p->type) }}</span>
  </td>
  <td><b>LKR {{ number_format($p->amount, 2) }}</b></td>
  <td>{{ $p->validity ?? '—' }}</td>
  <td>@if ($p->plan_code)<code style="font-size:12px;">{{ $p->plan_code }}</code>@else — @endif</td>
  <td><span class="pill pill--{{ $p->is_active ? 'success' : 'failed' }}">{{ $p->is_active ? 'Active' : 'Inactive' }}</span></td>
  <td>
    <div class="row-actions">
      <button type="button" class="btn-admin btn-admin--ghost btn-admin--sm" data-edit-plan data-plan='{{ $planJson }}'>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
        Edit
      </button>
      <form method="POST" action="{{ route('admin.plans.toggle', $p) }}" data-plan-toggle style="display:inline;">
        @csrf
        <button class="btn-admin btn-admin--ghost btn-admin--sm" type="submit">
          <span class="btn-label">
            @if ($p->is_active)
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"></rect><line x1="6" y1="9" x2="18" y2="9"></line><line x1="18" y1="4" x2="18" y2="16"></line><line x1="14" y1="16" x2="22" y2="16"></line></svg>
              Disable
            @else
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
              Enable
            @endif
          </span>
          <span class="btn-spinner" hidden></span>
        </button>
      </form>
      <button type="button" class="btn-admin btn-admin--danger btn-admin--sm" data-del-plan="{{ $p->id }}" data-name="{{ $p->name }}" data-url="{{ route('admin.plans.destroy', $p) }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path></svg>
        Delete
      </button>
    </div>
  </td>
</tr>
