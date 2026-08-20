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
        if ($status = $request->input('status')) {
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
            'category_id' => 'nullable|exists:categories,id',
            'type'        => 'required|in:prepaid,postpaid,broadband,utility,tv,insurance,dth,wallet,api',
            'logo'        => 'nullable|string|max:255',
            'profit'      => 'required|numeric|min:0',
            'profit_type' => 'required|in:FLAT,PCT',
            'is_active'   => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $service->fill($data)->save();

        return redirect()->route('admin.services.index')->with('status', "Service \"{$service->name}\" updated.");
    }

    public function bulkProfit(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'         => 'required|array',
            'ids.*'       => 'exists:services,id',
            'profit'      => 'required|numeric|min:0',
            'profit_type' => 'required|in:FLAT,PCT',
        ]);

        Service::whereIn('id', $request->ids)->update([
            'profit'      => $request->profit,
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
