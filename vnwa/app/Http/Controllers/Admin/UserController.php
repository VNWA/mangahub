<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        // Chỉ hiển thị users (không bao gồm admin)
        $query = User::withCount(['favorites', 'readingHistory', 'coinTransactions'])
            ->role('user')
            ->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_guest')) {
            $query->where('is_guest', $request->is_guest === 'true');
        }

        $users = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/User/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'is_guest']),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $user->loadCount(['favorites', 'readingHistory', 'coinTransactions', 'chapterUnlocks']);

        // Load comments with pagination
        $commentsQuery = \App\Models\Comment::where('user_id', $user->id)
            ->with(['page' => function ($q) {
                $q->with('pageable:id,name,slug');
            }])
            ->latest();

        $comments = $commentsQuery->paginate(15, ['*'], 'comments_page')
            ->withQueryString();

        // Load coin transactions with pagination
        $transactionsQuery = $user->coinTransactions()
            ->with('admin:id,name,email')
            ->latest();

        $transactions = $transactionsQuery->paginate(15, ['*'], 'transactions_page')
            ->withQueryString();

        $stats = [
            'total_favorites' => $user->favorites()->count(),
            'total_reading_history' => $user->readingHistory()->count(),
            'total_coin_transactions' => $user->coinTransactions()->count(),
            'total_chapter_unlocks' => $user->chapterUnlocks()->count(),
            'total_coins' => $user->coin,
            'total_comments' => \App\Models\Comment::where('user_id', $user->id)->count(),
        ];

        return Inertia::render('Admin/User/Show', [
            'user' => $user,
            'comments' => $comments,
            'transactions' => $transactions,
            'stats' => $stats,
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/User/Edit', [
            'user' => $user,
        ]);
    }

    public function addCoinPage(User $user): Response
    {
        return Inertia::render('Admin/User/AddCoin', [
            'user' => $user->only(['id', 'name', 'email', 'coin']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'coin' => ['required', 'integer', 'min:0'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.show', $user)
            ->with('success', 'Thông tin người dùng đã được cập nhật thành công.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Không cho phép xóa chính mình
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'Bạn không thể xóa chính mình.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Người dùng đã được xóa thành công.');
    }

    public function addCoin(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $transaction = $user->addCoin(
            $validated['amount'],
            $validated['description'] ?? 'Admin thêm coin',
            'Admin',
            auth()->id(),
            auth()->id() // admin_id
        );

        return response()->json([
            'success' => true,
            'message' => "Đã thêm {$validated['amount']} coin cho người dùng.",
            'transaction' => $transaction->load('admin:id,name,email'),
        ]);
    }

    public function removeCoin(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->coin < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng không đủ coin để trừ.',
            ], 422);
        }

        $transaction = $user->spendCoin(
            $validated['amount'],
            $validated['description'] ?? 'Admin trừ coin',
            'Admin',
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => "Đã trừ {$validated['amount']} coin của người dùng.",
            'transaction' => $transaction,
        ]);
    }

    public function assignRole(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,user'],
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật vai trò của người dùng thành {$validated['role']}.",
        ]);
    }
}
