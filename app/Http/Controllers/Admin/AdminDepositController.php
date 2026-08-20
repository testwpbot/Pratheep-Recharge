<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DepositApproved;
use App\Mail\DepositRejected;
use App\Models\WalletDeposit;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminDepositController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $deposits = WalletDeposit::with('user')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        $counts = [
            'pending'  => WalletDeposit::where('status', 'pending')->count(),
            'approved' => WalletDeposit::where('status', 'approved')->count(),
            'rejected' => WalletDeposit::where('status', 'rejected')->count(),
        ];

        return view('admin.deposits.index', compact('deposits', 'status', 'counts'));
    }

    public function show(WalletDeposit $deposit): View
    {
        $deposit->load('user.wallet', 'approver');
        return view('admin.deposits.show', compact('deposit'));
    }

    public function approve(Request $request, WalletDeposit $deposit, WalletService $wallet)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $deposit = $wallet->approve($deposit, auth()->id(), $validated['admin_note'] ?? null);
        $deposit->load('user.wallet');

        // Notify customer
        $emailSent = false;
        try {
            Mail::to($deposit->user->email)->send(new DepositApproved($deposit));
            $emailSent = true;
        } catch (\Throwable $e) {
            \Log::warning('Deposit approved email failed: ' . $e->getMessage());
        }

        $msg = 'Deposit approved — wallet credited with LKR ' . number_format($deposit->amount, 2) . '.';
        if (!$emailSent) {
            $msg .= ' (Email to customer failed — check SMTP settings.)';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'         => true,
                'message'    => $msg,
                'email_sent' => $emailSent,
                'deposit'    => [
                    'id'           => $deposit->id,
                    'status'       => $deposit->status,
                    'approved_at'  => $deposit->approved_at?->toDateTimeString(),
                    'wallet_bal'   => (float) $deposit->user->wallet->balance,
                ],
            ]);
        }

        return back()->with('success', $msg);
    }

    public function reject(Request $request, WalletDeposit $deposit, WalletService $wallet)
    {
        $validated = $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        $deposit = $wallet->reject($deposit, auth()->id(), $validated['admin_note']);
        $deposit->load('user');

        $emailSent = false;
        try {
            Mail::to($deposit->user->email)->send(new DepositRejected($deposit));
            $emailSent = true;
        } catch (\Throwable $e) {
            \Log::warning('Deposit rejected email failed: ' . $e->getMessage());
        }

        $msg = 'Deposit rejected.';
        if (!$emailSent) {
            $msg .= ' (Email to customer failed — check SMTP settings.)';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'         => true,
                'message'    => $msg,
                'email_sent' => $emailSent,
                'deposit'    => ['id' => $deposit->id, 'status' => $deposit->status],
            ]);
        }

        return back()->with('success', $msg);
    }
}
