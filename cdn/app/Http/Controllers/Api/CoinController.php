<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChapterUnlock;
use App\Models\CoinTransaction;
use App\Models\MangaChapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoinController extends Controller
{
    /**
     * Get user coin balance
     */
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'coin' => $user->coin ?? 0,
            ],
        ]);
    }

    /**
     * Deposit coin (nạp coin)
     */
    public function deposit(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $transaction = $user->addCoin(
                $request->amount,
                $request->description ?? 'Nạp coin',
                null,
                null
            );

            return response()->json([
                'ok' => true,
                'message' => 'Nạp coin thành công',
                'data' => [
                    'transaction' => [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'balance_after' => $transaction->balance_after,
                        'created_at' => $transaction->created_at->toISOString(),
                    ],
                    'balance' => $user->fresh()->coin,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Không thể nạp coin: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get coin transactions history
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $type = $request->query('type'); // deposit, spend, or null for all
        $perPage = min($request->query('per_page', 20), 100);
        $page = $request->query('page', 1);

        $query = CoinTransaction::where('user_id', $user->id);

        if ($type && in_array($type, ['deposit', 'spend'])) {
            $query->where('type', $type);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'ok' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Unlock a chapter
     */
    public function unlockChapter(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'chapter_id' => 'required|exists:manga_chapters,id',
        ]);

        $chapter = MangaChapter::findOrFail($request->chapter_id);

        // Check if chapter is locked
        if (! $chapter->is_locked) {
            return response()->json([
                'ok' => false,
                'message' => 'Chapter này không bị khóa',
            ], 400);
        }

        // Check if already unlocked
        if ($user->hasUnlockedChapter($chapter->id)) {
            return response()->json([
                'ok' => false,
                'message' => 'Bạn đã mở khóa chapter này rồi',
            ], 400);
        }

        // Check if user has enough coin
        if ($user->coin < $chapter->coin_cost) {
            return response()->json([
                'ok' => false,
                'message' => 'Không đủ coin để mở khóa. Cần '.$chapter->coin_cost.' coin',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Spend coin
            $transaction = $user->spendCoin(
                $chapter->coin_cost,
                "Mở khóa chapter: {$chapter->name}",
                MangaChapter::class,
                $chapter->id
            );

            // Create unlock record
            $unlock = ChapterUnlock::create([
                'user_id' => $user->id,
                'manga_chapter_id' => $chapter->id,
                'coin_spent' => $chapter->coin_cost,
            ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Mở khóa chapter thành công',
                'data' => [
                    'unlock' => [
                        'id' => $unlock->id,
                        'chapter_id' => $unlock->manga_chapter_id,
                        'coin_spent' => $unlock->coin_spent,
                        'created_at' => $unlock->created_at->toISOString(),
                    ],
                    'transaction' => [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'balance_after' => $transaction->balance_after,
                    ],
                    'balance' => $user->fresh()->coin,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Không thể mở khóa chapter: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unlock history
     */
    public function unlockHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $perPage = min($request->query('per_page', 20), 100);
        $page = $request->query('page', 1);

        $unlocks = ChapterUnlock::where('user_id', $user->id)
            ->with(['chapter.manga:id,name,slug'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'ok' => true,
            'data' => $unlocks->getCollection()->map(function ($unlock) {
                return [
                    'id' => $unlock->id,
                    'chapter' => [
                        'id' => $unlock->chapter->id,
                        'name' => $unlock->chapter->name,
                        'slug' => $unlock->chapter->slug,
                        'order' => $unlock->chapter->order,
                    ],
                    'manga' => [
                        'id' => $unlock->chapter->manga->id,
                        'name' => $unlock->chapter->manga->name,
                        'slug' => $unlock->chapter->manga->slug,
                    ],
                    'coin_spent' => $unlock->coin_spent,
                    'created_at' => $unlock->created_at->toISOString(),
                ];
            }),
            'pagination' => [
                'current_page' => $unlocks->currentPage(),
                'last_page' => $unlocks->lastPage(),
                'per_page' => $unlocks->perPage(),
                'total' => $unlocks->total(),
            ],
        ]);
    }
}
