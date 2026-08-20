<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Services\ServiceImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProviderController extends Controller
{
    public function index(): View
    {
        $providers = Provider::all();
        return view('admin.providers.index', compact('providers'));
    }

    public function edit(Provider $provider): View
    {
        return view('admin.providers.edit', compact('provider'));
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'base_url'  => 'nullable|url|max:255',
            'api_key'   => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->filled('api_key')) {
            $provider->api_key = $data['api_key'];
        }
        $provider->fill([
            'name'      => $data['name'],
            'base_url'  => $data['base_url'] ?? $provider->base_url,
            'is_active' => $data['is_active'],
        ])->save();

        // Clear cached balance when settings change
        cache()->forget("provider:{$provider->id}:balance");

        return redirect()->route('admin.providers.index')->with('status', "Provider {$provider->name} updated.");
    }

    public function toggle(Request $request, Provider $provider)
    {
        $provider->update(['is_active' => ! $provider->is_active]);
        $message = "Provider {$provider->name} " . ($provider->is_active ? 'activated.' : 'deactivated.');

        if ($request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => $message,
                'active'  => (bool) $provider->is_active,
            ]);
        }
        return back()->with('status', $message);
    }

    /**
     * Import services from a provider.
     */
    public function import(Request $request, Provider $provider, ServiceImporter $importer)
    {
        try {
            $result = $importer->importFromProvider($provider);
            $message = "Imported {$result['imported']} new service(s) from {$provider->name}; "
                     . "{$result['skipped']} existing, {$result['catCount']} new categories.";

            if ($request->wantsJson()) {
                return response()->json(array_merge(['ok' => true, 'message' => $message], $result));
            }
            return redirect()->route('admin.services.index')->with('status', $message);
        } catch (\Throwable $e) {
            $message = 'Import failed: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }
}
