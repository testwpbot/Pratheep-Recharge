<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ComplaintStatusUpdated;
use App\Models\Complaint;
use App\Support\HistoryPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $query = Complaint::with(['user', 'order']);

        $status = (string) $request->input('status', 'all');
        $allowed = ['open', 'in_progress', 'resolved', 'rejected'];
        if (in_array($status, $allowed, true)) {
            $query->where('status', $status);
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) =>
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('order', fn ($o) =>
                      $o->where('reference', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%"));
            });
        }

        $period = HistoryPeriod::fromRequest($request);
        if (! in_array($status, ['open', 'in_progress'], true)) {
            $period->apply($query, 'created_at', fn ($q) => $q->whereIn('status', ['open', 'in_progress']));
        }

        $complaints = $query->latest()->paginate(30)->withQueryString();

        $counts = [
            'all'         => Complaint::count(),
            'open'        => Complaint::where('status', 'open')->count(),
            'in_progress' => Complaint::where('status', 'in_progress')->count(),
            'resolved'    => Complaint::where('status', 'resolved')->count(),
            'rejected'    => Complaint::where('status', 'rejected')->count(),
        ];

        return view('admin.complaints.index', compact('complaints', 'counts', 'status'));
    }

    public function show(Complaint $complaint): View
    {
        $complaint->load(['user', 'order', 'handler']);
        return view('admin.complaints.show', compact('complaint'));
    }

    public function reply(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'status'     => 'required|in:open,in_progress,resolved,rejected',
            'admin_note' => 'nullable|string|max:3000',
        ]);

        $oldStatus = $complaint->status;
        $newStatus = $data['status'];
        $needsNote = in_array($newStatus, ['resolved', 'rejected'], true);

        if ($needsNote && empty($data['admin_note'])) {
            return back()->with('error', 'Please add a reply note when resolving or rejecting.');
        }

        $complaint->status = $newStatus;
        $complaint->admin_note = $data['admin_note'];
        $complaint->handled_by = auth()->id();
        if (in_array($newStatus, ['resolved', 'rejected'], true)) {
            $complaint->resolved_at = now();
        }
        $complaint->save();

        // Email the customer if the status changed (or a note was added)
        if ($oldStatus !== $newStatus || !empty($data['admin_note'])) {
            try {
                Mail::to($complaint->user->email)
                    ->send(new ComplaintStatusUpdated($complaint->fresh()->load('handler')));
            } catch (\Throwable $e) {
                \Log::warning('Complaint status email failed: ' . $e->getMessage());
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => 'Complaint updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.',
                'status'  => $newStatus,
            ]);
        }

        return back()->with('status', 'Complaint updated and customer notified.');
    }
}
