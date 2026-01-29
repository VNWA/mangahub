<?php

namespace App\Http\Controllers\Api;

use App\Events\CommentCreated;
use App\Events\CommentReplied;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\Manga;
use App\Models\MangaChapter;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    /**
     * Get comments for a page (Manga or MangaChapter)
     * Returns comments grouped by root_id (thread-based)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'commentable_type' => 'required|in:Manga,MangaChapter',
            'commentable_id' => 'required|integer',
            'root_id' => 'nullable|integer', // Load more replies for a specific root_id
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $commentableType = $request->commentable_type;
        $commentableId = $request->commentable_id;
        $rootId = $request->query('root_id');
        $currentPage = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 10), 50); // Mặc định 10 root comments

        // Resolve commentable
        $commentable = $commentableType === 'Manga'
            ? Manga::findOrFail($commentableId)
            : MangaChapter::findOrFail($commentableId);

        $page = Page::getOrCreateFor($commentable);

        // If root_id is provided, load more replies for that thread
        if ($rootId) {
            return $this->loadThreadReplies($page, $rootId, $currentPage, $perPage);
        }

        // Load root comments (threads) - comments with root_id = null or root_id = id
        $cacheKey = sprintf(
            'comments:page:%d:roots:p:%d:pp:%d',
            $page->id,
            $currentPage,
            $perPage
        );

        $result = Cache::tags(["comments:page:{$page->id}"])
            ->remember($cacheKey, 300, function () use ($page, $currentPage, $perPage) {
                // Get root comments (where root_id is null)
                $query = Comment::where('page_id', $page->id)
                    ->whereNull('root_id') // Root comments có root_id = null
                    ->with([
                        'user:id,name,email,avatar',
                        'reactions' => fn ($q) => $q->where('user_id', auth()->id()),
                    ])
                    ->orderBy('is_pinned', 'desc')
                    ->orderBy('likes_count', 'desc')
                    ->orderBy('created_at', 'desc');

                return $query->paginate($perPage, ['*'], 'page', $currentPage);
            });

        $rootComments = collect($result->items());

        // Load initial replies for each root comment (3 replies đầu tiên)
        if ($rootComments->isNotEmpty()) {
            $rootIds = $rootComments->pluck('id');

            // Load 3 replies đầu tiên cho mỗi root
            $replies = Comment::whereIn('root_id', $rootIds)
                ->with([
                    'user:id,name,email,avatar',
                    'parent.user:id,name', // Load parent user để hiển thị "Trả lời @username"
                    'reactions' => fn ($q) => $q->where('user_id', auth()->id()),
                ])
                ->orderBy('root_id')
                ->orderBy('created_at', 'asc')
                ->get()
                ->groupBy('root_id')
                ->map(function ($group) {
                    // Chỉ lấy 3 replies đầu tiên
                    return $group->take(3);
                });

            // Build thread structure
            $threads = $rootComments->map(function ($rootComment) use ($replies) {
                $threadReplies = $replies->get($rootComment->id, collect());
                $totalReplies = Comment::where('root_id', $rootComment->id)->count();

                return [
                    'root' => $this->transformComment($rootComment, collect()),
                    'replies' => $threadReplies->map(fn ($reply) => $this->transformComment($reply, collect()))->values(),
                    'total_replies' => $totalReplies,
                    'loaded_replies' => $threadReplies->count(),
                ];
            });
        } else {
            $threads = collect();
        }

        return response()->json([
            'ok' => true,
            'data' => $threads->values(),
            'page_id' => $page->id,
            'pagination' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ],
        ]);
    }

    /**
     * Load more replies for a specific thread (root_id)
     */
    private function loadThreadReplies(Page $page, int $rootId, int $pageNum, int $perPage): JsonResponse
    {
        // Verify root comment exists (root_id must be null)
        $rootComment = Comment::where('page_id', $page->id)
            ->where('id', $rootId)
            ->whereNull('root_id') // Root comment có root_id = null
            ->firstOrFail();

        $cacheKey = sprintf(
            'comments:root:%d:replies:p:%d:pp:%d',
            $rootId,
            $pageNum,
            $perPage
        );

        $result = Cache::tags(["comments:page:{$page->id}", "comments:root:{$rootId}"])
            ->remember($cacheKey, 300, function () use ($page, $rootId, $pageNum, $perPage) {
                $query = Comment::where('page_id', $page->id)
                    ->where('root_id', $rootId)
                    ->where('id', '!=', $rootId) // Exclude root comment
                    ->with([
                        'user:id,name,email,avatar',
                        'parent.user:id,name', // Load parent user để hiển thị "Trả lời @username"
                        'reactions' => fn ($q) => $q->where('user_id', auth()->id()),
                    ])
                    ->orderBy('created_at', 'asc'); // Đơn giản, chỉ sort theo thời gian

                return $query->paginate($perPage, ['*'], 'page', $pageNum);
            });

        $replies = collect($result->items())
            ->map(fn ($reply) => $this->transformComment($reply, collect()));

        return response()->json([
            'ok' => true,
            'data' => $replies->values(),
            'root_id' => $rootId,
            'pagination' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $validate = $request->validate([
            'commentable_type' => 'required|in:Manga,MangaChapter',
            'commentable_id' => 'required|integer',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'content' => 'required|string|min:1|max:5000',
        ]);

        $commentable = $request->commentable_type === 'Manga'
            ? Manga::findOrFail($request->commentable_id)
            : MangaChapter::findOrFail($request->commentable_id);

        $page = Page::getOrCreateFor($commentable);

        $parent = null;
        $rootId = null;
        $depth = 0;

        if ($request->parent_id) {
            $parent = Comment::findOrFail($request->parent_id);
            // Root ID: nếu parent là root (root_id = null), thì rootId = parent.id
            // Nếu parent là reply (có root_id), thì rootId = parent.root_id
            $rootId = $parent->root_id ?? $parent->id;
            $depth = ($parent->depth ?? 0) + 1;
        }
        // Nếu không có parent_id, rootId vẫn là null (root comment)

        $comment = Comment::create([
            'user_id' => $user->id,
            'page_id' => $page->id,
            'parent_id' => $request->parent_id,
            'root_id' => $rootId, // null cho root comments, id của root comment cho replies
            'depth' => $depth,
            'content' => $validate['content'],
        ]);

        if ($parent) {
            $parent->incrementRepliesCount();
        }

        $page->updateCommentsCount();

        // Clear cache
        Cache::tags(["comments:page:{$page->id}"])->flush();
        if ($rootId) {
            // rootId là id của root comment (parent là root)
            Cache::tags(["comments:root:{$rootId}"])->flush();
        } else {
            // Nếu là root comment mới, clear cache của page
            Cache::tags(["comments:page:{$page->id}"])->flush();
        }

        $comment->load(['user:id,name,email,avatar']);

        event(new CommentCreated($comment));
        if ($parent) {
            event(new CommentReplied($comment, $parent));
        }

        return response()->json([
            'ok' => true,
            'message' => 'Comment created successfully',
            'data' => $this->transformComment($comment, collect()),
        ], 201);
    }

    /**
     * Update a comment
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validate = $request->validate([
            'content' => 'required|string|min:1|max:5000',
        ]);

        $comment->update([
            'content' => $validate['content'],
            'is_edited' => true,
        ]);

        $comment->load(['user:id,name,email,avatar']);

        return response()->json([
            'ok' => true,
            'message' => 'Comment updated successfully',
            'data' => $this->transformComment($comment, collect()),
        ]);
    }

    /**
     * Delete a comment
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        DB::transaction(function () use ($comment) {
            // Decrement parent's replies count
            if ($comment->parent_id) {
                $parent = Comment::find($comment->parent_id);
                if ($parent) {
                    $parent->decrementRepliesCount();
                }
            }

            // Delete all reactions
            CommentReaction::where('comment_id', $comment->id)->delete();

            // Soft delete the comment
            $comment->delete();
        });

        return response()->json([
            'ok' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Like or dislike a comment
     */
    public function react(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'type' => 'required|in:like,dislike',
        ]);

        DB::transaction(function () use ($comment, $user, $request) {
            $existingReaction = CommentReaction::where('user_id', $user->id)
                ->where('comment_id', $comment->id)
                ->first();

            if ($existingReaction) {
                // If same type, remove reaction
                if ($existingReaction->type === $request->type) {
                    if ($existingReaction->type === 'like') {
                        $comment->decrement('likes_count');
                    } else {
                        $comment->decrement('dislikes_count');
                    }
                    $existingReaction->delete();
                } else {
                    // Change reaction type
                    if ($existingReaction->type === 'like') {
                        $comment->decrement('likes_count');
                        $comment->increment('dislikes_count');
                    } else {
                        $comment->decrement('dislikes_count');
                        $comment->increment('likes_count');
                    }
                    $existingReaction->update(['type' => $request->type]);
                }
            } else {
                // Create new reaction
                CommentReaction::create([
                    'user_id' => $user->id,
                    'comment_id' => $comment->id,
                    'type' => $request->type,
                ]);

                if ($request->type === 'like') {
                    $comment->increment('likes_count');
                } else {
                    $comment->increment('dislikes_count');
                }
            }

            $comment->refresh();
        });

        $userReaction = CommentReaction::where('user_id', $user->id)
            ->where('comment_id', $comment->id)
            ->first();

        return response()->json([
            'ok' => true,
            'message' => 'Reaction updated',
            'data' => [
                'likes_count' => $comment->likes_count,
                'dislikes_count' => $comment->dislikes_count,
                'user_reaction' => $userReaction ? $userReaction->type : null,
            ],
        ]);
    }

    /**
     * Transform comment for API response
     */
    private function transformComment(Comment $comment, $replies = null): array
    {
        $user = auth()->user();
        $userReaction = null;

        if ($user) {
            $reaction = CommentReaction::where('user_id', $user->id)
                ->where('comment_id', $comment->id)
                ->first();
            $userReaction = $reaction ? $reaction->type : null;
        }

        $data = [
            'id' => $comment->id,
            'content' => $comment->content,
            'likes_count' => $comment->likes_count,
            'dislikes_count' => $comment->dislikes_count,
            'replies_count' => $comment->replies_count,
            'is_edited' => $comment->is_edited,
            'is_pinned' => $comment->is_pinned,
            'root_id' => $comment->root_id,
            'depth' => $comment->depth ?? 0,
            'parent_id' => $comment->parent_id,
            'user_reaction' => $userReaction,
            'user' => $comment->user ? [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'email' => $comment->user->email,
                'avatar' => $comment->user->avatar,
            ] : null,
            'parent_user' => $comment->parent && $comment->parent->user ? [
                'id' => $comment->parent->user->id,
                'name' => $comment->parent->user->name,
            ] : null, // Thông tin user của comment mà reply này đang reply
            'created_at' => $comment->created_at->toISOString(),
            'updated_at' => $comment->updated_at->toISOString(),
            'is_owner' => $user && $comment->user_id === $user->id,
        ];

        if ($replies !== null) {
            $data['replies'] = $replies->map(function ($reply) {
                return $this->transformComment($reply, collect());
            })->values();
        }

        return $data;
    }
}
