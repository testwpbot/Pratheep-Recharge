<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Support\SafeHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAlertController extends Controller
{
    public function index(): View
    {
        $alerts = Alert::query()->orderBy('sort_order')->orderByDesc('id')->paginate(20);

        return view('admin.alerts.index', compact('alerts'));
    }

    public function create(): View
    {
        $alert = new Alert([
            'theme'          => Alert::THEME_NAVY,
            'audience'       => Alert::AUDIENCE_ALL,
            'is_active'      => true,
            'is_dismissible' => true,
            'sort_order'     => 0,
        ]);

        return view('admin.alerts.form', compact('alert'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $alert = Alert::create($data);
        $this->storeImage($request, $alert);

        return redirect()
            ->route('admin.alerts.index')
            ->with('success', 'Alert is ready. Logged-in customers will see it on the dashboard.');
    }

    public function edit(Alert $alert): View
    {
        return view('admin.alerts.form', compact('alert'));
    }

    public function update(Request $request, Alert $alert): RedirectResponse
    {
        $alert->fill($this->validated($request))->save();
        $this->storeImage($request, $alert);

        if ($request->boolean('remove_image') && ! $request->hasFile('image')) {
            $this->deleteImage($alert);
            $alert->forceFill(['image_path' => null])->save();
        }

        return redirect()
            ->route('admin.alerts.index')
            ->with('success', 'Alert saved.');
    }

    public function destroy(Alert $alert): RedirectResponse
    {
        $this->deleteImage($alert);
        $alert->delete();

        return redirect()->route('admin.alerts.index')->with('success', 'Alert removed.');
    }

    public function toggle(Alert $alert): RedirectResponse
    {
        $alert->is_active = ! $alert->is_active;
        $alert->save();

        return back()->with('success', $alert->is_active
            ? 'Alert is on. Customers can see it on the dashboard.'
            : 'Alert is off.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title'          => 'required|string|max:160',
            'eyebrow'        => 'nullable|string|max:80',
            'heading'        => 'required|string|max:180',
            'body'           => 'nullable|string|max:8000',
            'image'          => 'nullable|image|max:2048',
            'button_label'   => 'nullable|string|max:80',
            'button_url'     => 'nullable|string|max:500',
            'button2_label'  => 'nullable|string|max:80',
            'button2_url'    => 'nullable|string|max:500',
            'theme'          => 'required|in:navy,gold',
            'audience'       => 'required|in:all,customers,retailers',
            'starts_at'      => 'nullable|date',
            'ends_at'        => 'nullable|date|after_or_equal:starts_at',
            'sort_order'     => 'nullable|integer|min:0|max:9999',
        ]);

        unset($data['image']);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_dismissible'] = $request->boolean('is_dismissible');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['button_url'] = Alert::safeUrl($data['button_url'] ?? null);
        $data['button2_url'] = Alert::safeUrl($data['button2_url'] ?? null);
        $data['eyebrow'] = trim((string) ($data['eyebrow'] ?? '')) ?: null;
        $body = SafeHtml::clean((string) ($data['body'] ?? ''));
        $data['body'] = $body !== '' ? $body : null;
        $data['button_label'] = trim((string) ($data['button_label'] ?? '')) ?: null;
        $data['button2_label'] = trim((string) ($data['button2_label'] ?? '')) ?: null;

        return $data;
    }

    protected function storeImage(Request $request, Alert $alert): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $dir = public_path('uploads/alerts');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->deleteImage($alert);

        $name = 'alert-' . $alert->id . '-' . time() . '.' . $request->file('image')->getClientOriginalExtension();
        $request->file('image')->move($dir, $name);
        $alert->forceFill(['image_path' => 'uploads/alerts/' . $name])->save();
    }

    protected function deleteImage(Alert $alert): void
    {
        if (! $alert->image_path) {
            return;
        }
        $abs = public_path($alert->image_path);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
