<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Models\SpecialPrice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminSpecialPricingController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('is_admin', false)
            ->withCount('specialPrices')
            ->orderByDesc('is_retailer')
            ->orderBy('name');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $filter = $request->input('filter', 'all');
        if ($filter === 'retailers') {
            $query->where('is_retailer', true);
        } elseif ($filter === 'special') {
            $query->has('specialPrices');
        }

        $users = $query->paginate(30)->withQueryString();

        return view('admin.special-pricing.index', compact('users', 'filter'));
    }

    public function edit(User $user): View
    {
        abort_if($user->is_admin, 404);

        $overrides = SpecialPrice::where('user_id', $user->id)->get()->keyBy('service_id');

        $categories = Category::where('is_active', true)
            ->with(['services' => fn ($q) => $q->where('is_active', true)->with('provider')->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        return view('admin.special-pricing.edit', compact('user', 'categories', 'overrides'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 404);

        $rows = $request->input('rows', []);
        if (! is_array($rows)) {
            $rows = [];
        }

        $services = Service::where('is_active', true)->with('category')->get()->keyBy('id');

        // A negative special profit is a customer fee (surcharge) — exactly like
        // the service management page. It is only allowed on bill-like services
        // (utility / postpaid / insurance / wallet). Validate before saving so
        // the admin gets a clear error instead of a silent clamp.
        $badFee = [];
        foreach ($services as $sid => $service) {
            $row = $rows[$sid] ?? null;
            if (! is_array($row) || empty($row['enabled'])) {
                continue;
            }
            $profit = (float) ($row['profit'] ?? 0);
            $type   = (($row['profit_type'] ?? 'FLAT') === 'PCT') ? 'PCT' : 'FLAT';
            if ($profit < 0) {
                if (! $service->allowsFee()) {
                    $badFee[] = $service->name;
                } elseif ($type === 'PCT' && $profit < -100) {
                    return back()->withInput()->withErrors([
                        'rows' => 'A percentage fee cannot be more than 100%.',
                    ]);
                }
            }
        }
        if (! empty($badFee)) {
            return back()->withInput()->withErrors([
                'rows' => 'A negative profit (customer fee) is only allowed on bill-type services. '
                    . 'These are not bill services: ' . implode(', ', array_slice($badFee, 0, 5))
                    . (count($badFee) > 5 ? '…' : '') . '.',
            ]);
        }

        DB::transaction(function () use ($rows, $user, $services) {
            foreach ($services as $sid => $service) {
                $row = $rows[$sid] ?? null;
                $enabled = is_array($row) && ! empty($row['enabled']);
                if (! $enabled) {
                    SpecialPrice::where('user_id', $user->id)->where('service_id', $sid)->delete();
                    continue;
                }
                $type = (($row['profit_type'] ?? 'FLAT') === 'PCT') ? 'PCT' : 'FLAT';
                $profit = (float) ($row['profit'] ?? 0);
                // Fee (negative) only honoured on bill-like services; clamp others to >= 0.
                if ($profit < 0 && ! $service->allowsFee()) {
                    $profit = 0;
                }
                SpecialPrice::updateOrCreate(
                    ['user_id' => $user->id, 'service_id' => $sid],
                    ['profit' => $profit, 'profit_type' => $type]
                );
            }
        });

        if ($request->boolean('mark_retailer')) {
            $user->forceFill(['is_retailer' => true])->save();
        }

        return redirect()
            ->route('admin.special-pricing.edit', $user)
            ->with('status', "Special pricing saved for {$user->name}.");
    }

    public function bulk(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 404);

        $data = $request->validate([
            // Negative = customer fee (surcharge); only applied to bill-like services.
            'profit'      => 'required|numeric|min:-100000|max:100000',
            'profit_type' => 'required|in:FLAT,PCT',
        ]);

        $profit = (float) $data['profit'];
        if ($profit < 0 && $data['profit_type'] === 'PCT' && $profit < -100) {
            return back()->withInput()->withErrors([
                'profit' => 'A percentage fee cannot be more than 100%.',
            ]);
        }

        $services = Service::where('is_active', true)->with('category')->get();
        DB::transaction(function () use ($services, $user, $data, $profit) {
            foreach ($services as $service) {
                // A fee (negative) is only meaningful on bill-like services;
                // set every other service to 0 so nobody gets a surprise surcharge.
                $value = ($profit < 0 && ! $service->allowsFee()) ? 0 : $profit;
                SpecialPrice::updateOrCreate(
                    ['user_id' => $user->id, 'service_id' => $service->id],
                    ['profit' => $value, 'profit_type' => $data['profit_type']]
                );
            }
            $user->forceFill(['is_retailer' => true])->save();
        });

        $verb = $profit < 0 ? 'fee of' : 'profit of';
        $unit = $data['profit_type'] === 'PCT' ? '%' : ' LKR';
        return back()->with('status', "Applied {$verb} " . abs($profit) . $unit . " to every active service for {$user->name}"
            . ($profit < 0 ? ' (fee only on bill-type services).' : '.'));
    }

    public function clear(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 404);
        SpecialPrice::where('user_id', $user->id)->delete();
        return back()->with('status', "Cleared special pricing for {$user->name}. They will see default service profit.");
    }

    public function toggleRetailer(Request $request, User $user)
    {
        abort_if($user->is_admin, 404);
        $user->forceFill(['is_retailer' => ! $user->is_retailer])->save();
        $message = $user->name . ($user->is_retailer ? ' marked as retailer.' : ' unmarked as retailer.');

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'is_retailer' => $user->is_retailer]);
        }
        return back()->with('status', $message);
    }
}
