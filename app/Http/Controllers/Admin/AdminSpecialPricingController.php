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

        $serviceIds = Service::where('is_active', true)->pluck('id')->all();

        DB::transaction(function () use ($rows, $user, $serviceIds) {
            foreach ($serviceIds as $sid) {
                $row = $rows[$sid] ?? null;
                $enabled = is_array($row) && ! empty($row['enabled']);
                if (! $enabled) {
                    SpecialPrice::where('user_id', $user->id)->where('service_id', $sid)->delete();
                    continue;
                }
                $type = (($row['profit_type'] ?? 'FLAT') === 'PCT') ? 'PCT' : 'FLAT';
                $profit = max(0, (float) ($row['profit'] ?? 0));
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
            'profit'      => 'required|numeric|min:0',
            'profit_type' => 'required|in:FLAT,PCT',
        ]);

        $ids = Service::where('is_active', true)->pluck('id');
        DB::transaction(function () use ($ids, $user, $data) {
            foreach ($ids as $sid) {
                SpecialPrice::updateOrCreate(
                    ['user_id' => $user->id, 'service_id' => $sid],
                    ['profit' => $data['profit'], 'profit_type' => $data['profit_type']]
                );
            }
            $user->forceFill(['is_retailer' => true])->save();
        });

        return back()->with('status', "Applied {$data['profit']}" . ($data['profit_type'] === 'PCT' ? '%' : ' LKR') . " to every active service for {$user->name}.");
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
