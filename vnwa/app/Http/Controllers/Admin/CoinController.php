<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinRequest;
use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoinController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CoinTransaction::with(['user:id,name,email'])
            ->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $transactions = $query->paginate(30)->withQueryString();

        $stats = [
            'total_deposits' => CoinTransaction::where('type', 'deposit')->sum('amount'),
            'total_spends' => CoinTransaction::where('type', 'spend')->sum('amount'),
            'total_users' => User::where('coin', '>', 0)->count(),
            'total_coins_in_circulation' => User::sum('coin'),
            'pending_requests' => CoinRequest::where('status', 'pending')->count(),
            'pending_requests_amount' => CoinRequest::where('status', 'pending')->sum('amount'),
        ];

        return Inertia::render('admin/coin/Index', [
            'transactions' => $transactions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'user_id']),
        ]);
    }

    public function userTransactions(Request $request, User $user): Response
    {
        $query = $user->coinTransactions()
            ->latest();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate(30)->withQueryString();

        return Inertia::render('admin/coin/UserTransactions', [
            'user' => $user,
            'transactions' => $transactions,
            'filters' => $request->only(['type']),
        ]);
    }
}
