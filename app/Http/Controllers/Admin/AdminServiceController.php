<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Service::with(['provider', 'category']);

        if ($providerId = $request->input('provider_id')) {
            $query->where('provider_id', $providerId);
        }
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }
        // Default to Active so hidden Topup Mart DTH failover rows stay out of the list.
        $status = $request->input('status', 'active');
        if ($status === 'active' || $status === 'inactive') {
            $query->where('is_active', $status === 'active');
        }
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('op_code', 'like', "%{$search}%");
            });
        }

        $services  = $query->orderBy('category_id')->orderBy('name')->paginate(50)->withQueryString();
        $providers = Provider::orderBy('name')->get();
        $categories = Category::orderBy('sort_order')->get();

        return view('admin.services.index', compact('services', 'providers', 'categories'));
    }

    public function edit(Service $service): View
    {
        $providers  = Provider::orderBy('name')->get();
        $categories = Category::orderBy('sort_order')->get();
        return view('admin.services.edit', compact('service', 'providers', 'categories'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'op_code'     => [
                'required', 'string', 'max:20',
                \Illuminate\Validation\Rule::unique('services', 'op_code')
                    ->ignore($service->id)
                    ->where(fn ($q) => $q->where('provider_id', $service->provider_id)),
            ],
            'category_id' => 'nullable|exists:categories,id',
            'type'        => 'required|in:prepaid,postpaid,broadband,utility,tv,insurance,dth,wallet,api',
            'logo'        => 'nullable|string|max:255',
            // Profit can be NEGATIVE (a customer service fee) for bill-like
            // services. For everything else it stays a cashback (>= 0).
            'profit'      => 'required|numeric|min:-100000|max:100000',
            'profit_type' => 'required|in:FLAT,PCT',
            'is_active'   => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        // Guard: a negative profit (fee) is only allowed on bill-like services.
        // Re-evaluate against the SUBMITTED type/category, not the stored one.
        if ((float) $data['profit'] < 0) {
            $submittedType = strtolower((string) $data['type']);
            $slug = null;
            if (! empty($data['category_id'])) {
                $slug = strtolower((string) Category::whereKey($data['category_id'])->value('slug'));
            }
            $billLikeType = in_array($submittedType, ['utility', 'postpaid', 'bill', 'insurance', 'wallet'], true);
            $billLikeSlug = in_array((string) $slug, ['utility', 'insurance', 'wallet-topup'], true);
            if (! $billLikeType && ! $billLikeSlug) {
                return back()->withInput()->withErrors([
                    'profit' => 'A negative profit (customer fee) is only allowed on bill-type services (utility, postpaid, insurance, wallet).',
                ]);
            }
            // A percentage fee above 100% makes no sense.
            if ($data['profit_type'] === 'PCT' && (float) $data['profit'] < -100) {
                return back()->withInput()->withErrors([
                    'profit' => 'A percentage fee cannot be more than 100%.',
                ]);
            }
        }

        $service->fill($data)->save();

        return redirect()->route('admin.services.index')->with('status', "Service \"{$service->name}\" updated.");
    }

    public function bulkProfit(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'         => 'required|array',
            'ids.*'       => 'exists:services,id',
            // Negative = customer fee; only applied to bill-like services (below).
            'profit'      => 'required|numeric|min:-100000|max:100000',
            'profit_type' => 'required|in:FLAT,PCT',
        ]);

        $profit = (float) $request->profit;

        if ($profit < 0) {
            if ($request->profit_type === 'PCT' && $profit < -100) {
                return back()->withInput()->withErrors([
                    'profit' => 'A percentage fee cannot be more than 100%.',
                ]);
            }

            // A negative profit (fee) may only be set on bill-like services.
            $targets = Service::with('category')->whereIn('id', $request->ids)->get();
            $notBillLike = $targets->reject->allowsFee();
            if ($notBillLike->isNotEmpty()) {
                return back()->withInput()->withErrors([
                    'profit' => 'A negative profit (customer fee) is only allowed on bill-type services. '
                        . 'These are not bill services: ' . $notBillLike->pluck('name')->take(5)->implode(', ')
                        . ($notBillLike->count() > 5 ? '…' : '') . '.',
                ]);
            }
        }

        Service::whereIn('id', $request->ids)->update([
            'profit'      => $profit,
            'profit_type' => $request->profit_type,
        ]);

        return back()->with('status', 'Profit updated for ' . count($request->ids) . ' service(s).');
    }

    public function toggle(Request $request, Service $service)
    {
        $service->update(['is_active' => ! $service->is_active]);
        $message = "Service \"{$service->name}\" " . ($service->is_active ? 'activated.' : 'deactivated.');

        if ($request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => $message,
                'active'  => (bool) $service->is_active,
            ]);
        }
        return back()->with('status', $message);
    }
}
