<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $smtp = Setting::forGroup('smtp');
        $bank = Setting::forGroup('bank');
        $general = Setting::forGroup('general');

        return view('admin.settings.index', compact('smtp', 'bank', 'general'));
    }

    public function saveSmtp(Request $request)
    {
        $data = $request->validate([
            'host'         => 'required|string|max:255',
            'port'         => 'required|integer|min:1|max:65535',
            'username'     => 'nullable|string|max:255',
            'password'     => 'nullable|string|max:500',
            'encryption'   => 'nullable|in:tls,ssl,none',
            'from_address' => 'required|email|max:255',
            'from_name'    => 'required|string|max:120',
        ]);

        foreach ($data as $k => $v) {
            if ($k === 'password' && $v === '') continue; // don't overwrite with blank
            Setting::set('smtp', $k, $v);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'SMTP settings saved.']);
        }
        return back()->with('success', 'SMTP settings saved.');
    }

    public function saveBank(Request $request)
    {
        $data = $request->validate([
            'bank_name'    => 'required|string|max:160',
            'account_name' => 'required|string|max:160',
            'account_no'   => 'required|string|max:80',
            'branch'       => 'nullable|string|max:160',
            'instructions' => 'nullable|string|max:2000',
        ]);

        foreach ($data as $k => $v) {
            Setting::set('bank', $k, $v);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Bank details saved.']);
        }
        return back()->with('success', 'Bank details saved.');
    }

    public function saveGeneral(Request $request)
    {
        $data = $request->validate([
            'site_name'       => 'required|string|max:120',
            'support_email'   => 'nullable|email|max:255',
            'support_phone'   => 'nullable|string|max:40',
            'deposit_note'    => 'nullable|string|max:2000',
        ]);

        foreach ($data as $k => $v) {
            Setting::set('general', $k, $v);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'General settings saved.']);
        }
        return back()->with('success', 'General settings saved.');
    }

    /**
     * Send a test email to verify SMTP works.
     */
    public function testSmtp(Request $request)
    {
        $request->validate(['to' => 'required|email']);
        try {
            // First apply the form values so test uses the latest (even if unsaved in DB)
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host'       => $request->host,
                'mail.mailers.smtp.port'       => (int) $request->port,
                'mail.mailers.smtp.username'   => $request->username,
                'mail.mailers.smtp.password'   => $request->password ?: config('mail.mailers.smtp.password'),
                'mail.mailers.smtp.encryption' => ($request->encryption ?? 'tls') === 'none' ? null : ($request->encryption ?? 'tls'),
                'mail.from.address'            => $request->from_address,
                'mail.from.name'               => $request->from_name,
            ]);

            Mail::raw('This is a test email from Happy Pratheep Recharge. If you received this, SMTP is configured correctly bro! ✅', function ($msg) use ($request) {
                $msg->to($request->to)->subject('SMTP Test — Happy Pratheep Recharge');
            });

            return response()->json(['ok' => true, 'message' => 'Test email sent to ' . $request->to]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'SMTP error: ' . $e->getMessage()], 500);
        }
    }
}
