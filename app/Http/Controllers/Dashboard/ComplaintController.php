<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\ComplaintSubmitted;
use App\Models\Complaint;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Support\HistoryPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        $period = HistoryPeriod::fromRequest($request);

        $q = auth()->user()->complaints()->with('order');

        $allowed = ['open', 'in_progress', 'resolved', 'rejected'];
        if (in_array($status, $allowed, true)) {
            $q->where('status', $status);
        }
        if ($status !== 'open' && $status !== 'in_progress') {
            $period->apply($q, 'created_at', fn ($open) => $open->whereIn('status', ['open', 'in_progress']));
        }

        $complaints = $q->latest()->paginate(15)->appends($request->query());

        $counts = [
            'open'     => auth()->user()->complaints()->where('status', 'open')->count(),
            'progress' => auth()->user()->complaints()->where('status', 'in_progress')->count(),
            'resolved' => auth()->user()->complaints()->where('status', 'resolved')->count(),
            'rejected' => auth()->user()->complaints()->where('status', 'rejected')->count(),
        ];

        return view('dashboard.complaints', compact('complaints', 'status', 'period', 'counts'));
    }

    public function show(Complaint $complaint): View
    {
        abort_unless(auth()->id() === $complaint->user_id || auth()->user()?->is_admin, 403);
        $complaint->load('order', 'handler');
        return view('dashboard.complaint-show', compact('complaint'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'subject'  => 'required|string|max:160',
            'mobile'   => 'nullable|string|max:30',
            'reason'   => 'required|string|min:10|max:2000',
        ]);

        // Ownership check — customer can only complain about their own orders
        $order = Order::findOrFail($data['order_id']);
        abort_unless(auth()->id() === $order->user_id || auth()->user()?->is_admin, 403);

        $complaint = Complaint::create([
            'reference' => Complaint::generateReference(),
            'user_id'   => auth()->id(),
            'order_id'  => $order->id,
            'subject'   => $data['subject'],
            'mobile'    => $data['mobile'] ?: $order->account_number,
            'reason'    => $data['reason'],
            'status'    => 'open',
        ]);

        // Notify admin
        try {
            $adminEmail = Setting::get('general', 'support_email');
            if (!$adminEmail) {
                $adminEmail = User::where('is_admin', true)->value('email');
            }
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new ComplaintSubmitted($complaint->load('user', 'order')));
            }
        } catch (\Throwable $e) {
            \Log::warning('Complaint admin email failed: ' . $e->getMessage());
        }

        $msg = 'Complaint submitted! Reference ' . $complaint->reference . '. We\'ll review it shortly.';

        if ($request->wantsJson()) {
            return response()->json([
                'ok'        => true,
                'message'   => $msg,
                'redirect'  => route('complaints.show', $complaint),
                'complaint' => [
                    'id'        => $complaint->id,
                    'reference' => $complaint->reference,
                    'status'    => $complaint->status,
                ],
            ]);
        }

        return redirect()->route('complaints.show', $complaint)->with('success', $msg);
    }
}
