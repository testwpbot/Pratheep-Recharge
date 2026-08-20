<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function index(Request $request): View
    {
        $query = Plan::with(['service', 'service.provider']);

        if ($serviceId = $request->input('service_id')) {
            $query->where('service_id', $serviceId);
        }
        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'active');
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('plan_code', 'like', "%{$search}%")
                  ->orWhereHas('service', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        $plans = $query->orderBy('sort_order')->orderBy('amount')->paginate(25)->withQueryString();
        $services = Service::where('is_active', true)->orderBy('name')->get();

        return view('admin.plans.index', compact('plans', 'services'));
    }

    public function create(Request $request)
    {
        return redirect()->route('admin.plans.index');
    }

    public function store(Request $request)
    {
        $data = $this->validatePlan($request);
        $data['meta'] = $this->buildMeta($request);
        $plan = Plan::create($data);
        $plan->load('service');

        $msg = "Plan \"{$plan->name}\" created.";
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $msg, 'plan' => $this->toArray($plan)]);
        }
        return redirect()->route('admin.plans.index')->with('status', $msg);
    }

    public function edit(Plan $plan)
    {
        return redirect()->route('admin.plans.index');
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validatePlan($request);
        $data['meta'] = $this->buildMeta($request);
        $plan->fill($data)->save();
        $plan->load('service');

        $msg = "Plan \"{$plan->name}\" updated.";
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $msg, 'plan' => $this->toArray($plan)]);
        }
        return redirect()->route('admin.plans.index')->with('status', $msg);
    }

    public function destroy(Request $request, Plan $plan)
    {
        $name = $plan->name;
        $plan->delete();
        $msg = "Plan \"{$name}\" deleted.";
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $msg]);
        }
        return back()->with('status', $msg);
    }

    public function toggle(Request $request, Plan $plan)
    {
        $plan->update(['is_active' => ! $plan->is_active]);
        $msg = "Plan \"{$plan->name}\" " . ($plan->is_active ? 'activated.' : 'deactivated.');
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $msg, 'is_active' => $plan->is_active]);
        }
        return back()->with('status', $msg);
    }

    protected function validatePlan(Request $request): array
    {
        $data = $request->validate([
            'service_id'  => 'required|exists:services,id',
            'name'        => 'required|string|max:160',
            'plan_code'   => 'nullable|string|max:50',
            'amount'      => 'required|numeric|min:10|max:200000',
            'validity'    => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'type'        => 'required|in:reload,data,voice,combo,bill,utility,social,tv,postpaid',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]) + [
            'sort_order' => $request->input('sort_order', 0),
            'is_active'  => $request->boolean('is_active'),
        ];
        unset($data['detail_label'], $data['detail_value'], $data['detail_icon']);
        return $data;
    }

    /** Build meta JSON from the detail rows submitted. */
    protected function buildMeta(Request $request): ?array
    {
        $labels = $request->input('detail_label', []);
        $values = $request->input('detail_value', []);
        $icons  = $request->input('detail_icon', []);
        if (! is_array($labels)) {
            return null;
        }
        $rows = [];
        foreach ($labels as $i => $label) {
            $label = trim((string) ($label ?? ''));
            $value = trim((string) ($values[$i] ?? ''));
            if ($label === '' && $value === '') {
                continue;
            }
            $rows[] = [
                'label' => $label,
                'value' => $value,
                'icon'  => in_array((string) ($icons[$i] ?? ''), ['wifi','phone','grid','users','bolt','tv-card','bill','clock'], true) ? $icons[$i] : 'bolt',
            ];
        }
        return $rows ? ['details' => $rows] : null;
    }

    protected function toArray(Plan $plan): array
    {
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
        return [
            'id'            => $plan->id,
            'service_id'    => $plan->service_id,
            'service_name'  => $plan->service->name ?? '',
            'service_logo'  => $plan->service ? $plan->service->logoUrl : asset('assets/logo-mark.png'),
            'name'          => $plan->name,
            'amount'        => (float) $plan->amount,
            'type'          => $plan->type,
            'type_label'    => $typeLabels[$plan->type] ?? ucfirst($plan->type),
            'type_color'    => $typeColors[$plan->type] ?? 'pending',
            'validity'      => $plan->validity,
            'plan_code'     => $plan->plan_code,
            'description'   => $plan->description,
            'sort_order'    => (int) $plan->sort_order,
            'is_active'     => (bool) $plan->is_active,
            'details'       => $plan->meta['details'] ?? [],
            'edit_url'      => route('admin.plans.update', $plan),
            'toggle_url'    => route('admin.plans.toggle', $plan),
            'delete_url'    => route('admin.plans.destroy', $plan),
        ];
    }
}
