<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CoinRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CoinRequest::with(['user:id,name,email', 'processor:id,name'])
            ->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $requests = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => CoinRequest::where('status', 'pending')->count(),
            'approved' => CoinRequest::where('status', 'approved')->count(),
            'rejected' => CoinRequest::where('status', 'rejected')->count(),
            'total_amount_pending' => CoinRequest::where('status', 'pending')->sum('amount'),
            'total_amount_approved' => CoinRequest::where('status', 'approved')->sum('amount'),
        ];

        return Inertia::render('admin/coin/requests/Index', [
            'requests' => $requests,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'payment_method']),
        ]);
    }

    public function show(CoinRequest $coinRequest): Response
    {
        $coinRequest->load(['user:id,name,email,avatar,coin', 'processor:id,name,email']);

        return Inertia::render('admin/coin/requests/Show', [
            'request' => $coinRequest,
        ]);
    }

    public function approve(Request $request, CoinRequest $coinRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($coinRequest->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Yêu cầu này đã được xử lý.');
        }

        // Cấp coin cho user
        $user = $coinRequest->user;
        $user->addCoin(
            $coinRequest->amount,
            "Nạp coin từ yêu cầu #{$coinRequest->id}",
            CoinRequest::class,
            $coinRequest->id
        );

        // Cập nhật trạng thái
        $coinRequest->update([
            'status' => 'approved',
            'admin_note' => $validated['admin_note'] ?? null,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', "Đã duyệt yêu cầu và cấp {$coinRequest->amount} coin cho người dùng.");
    }

    public function reject(Request $request, CoinRequest $coinRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ], [
            'admin_note.required' => 'Vui lòng nhập lý do từ chối.',
        ]);

        if ($coinRequest->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Yêu cầu này đã được xử lý.');
        }

        // Cập nhật trạng thái
        $coinRequest->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'],
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Đã từ chối yêu cầu.');
    }

    public function destroy(CoinRequest $coinRequest): RedirectResponse
    {
        // Xóa ảnh chứng từ nếu có
        if ($coinRequest->payment_proof) {
            Storage::disk('public')->delete($coinRequest->payment_proof);
        }

        $coinRequest->delete();

        return redirect()->route('coin-requests.index')
            ->with('success', 'Yêu cầu đã được xóa thành công.');
    }
}
