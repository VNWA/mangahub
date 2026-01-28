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
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'commentable_type' => 'required|in:Manga,MangaChapter',
            'commentable_id' => 'required|integer',
            'parent_id' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $commentableType = $request->commentable_type;
        $commentableId = $request->commentable_id;
        $parentId = $request->query('parent_id');

        // Verify commentable exists and get/create page
        if ($commentableType === 'Manga') {
            $commentable = Manga::findOrFail($commentableId);
        } else {
            $commentable = MangaChapter::findOrFail($commentableId);
        }

        $page = Page::getOrCreateFor($commentable);

        $cacheKey = "comments:page:{$page->id}:parent:{$parentId}";
        $comments = Cache::remember($cacheKey, 300, function () use ($page, $parentId) {
            $query = Comment::where('page_id', $page->id)
                ->with([
                    'user:id,name,email,avatar',
                    'reactions' => function ($q) {
                        $q->where('user_id', auth()->id());
                    },
                ]);

            if ($parentId) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id');
            }

            // Order: pinned first, then by likes, then by date
            $query->orderBy('is_pinned', 'desc')
                ->orderBy('likes_count', 'desc')
                ->orderBy('created_at', 'desc');

            return $query->get();
        });

        // Only load direct replies (depth 1) for top-level comments
        // Deeper replies will be lazy-loaded on demand
        if (!$parentId) {
            $commentIds = $comments->pluck('id');
            // Only load depth 1 replies (direct children)
            $replies = Comment::whereIn('parent_id', $commentIds)
                ->where('depth', 1) // Only direct replies
                ->with(['user:id,name,email,avatar', 'reactions' => function ($q) {
                    $q->where('user_id', auth()->id());
                }])
                ->orderBy('created_at', 'asc')
                ->get()
                ->groupBy('parent_id');

            $comments = $comments->map(function ($comment) use ($replies) {
                return $this->transformComment($comment, $replies->get($comment->id, collect()));
            });
        } else {
            $comments = $comments->map(function ($comment) {
                return $this->transformComment($comment, collect());
            });
        }

        $perPage = min($request->query('per_page', 20), 50);
        $total = $comments->count();
        $currentPage = $request->query('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginated = $comments->slice($offset, $perPage)->values();

        return response()->json([
            'ok' => true,
            'data' => $paginated,
            'page_id' => $page->id,
            'pagination' => [
                'current_page' => $currentPage,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Create a new comment
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'commentable_type' => 'required|in:Manga,MangaChapter',
            'commentable_id' => 'required|integer',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'content' => 'required|string|min:1|max:5000',
        ]);

        // Verify commentable exists and get/create page
        if ($request->commentable_type === 'Manga') {
            $commentable = Manga::findOrFail($request->commentable_id);
        } else {
            $commentable = MangaChapter::findOrFail($request->commentable_id);
        }

        $page = Page::getOrCreateFor($commentable);

        // Calculate root_id and depth
        $rootId = null;
        $depth = 0;
        $parent = null;

        if ($request->parent_id) {
            $parent = Comment::findOrFail($request->parent_id);
            // Root ID is the root of the parent, or parent itself if parent is root
            $rootId = $parent->root_id ?? $parent->id;
            // Depth is parent's depth + 1
            $depth = ($parent->depth ?? 0) + 1;
        }

        $comment = Comment::create([
            'user_id' => $user->id,
            'page_id' => $page->id,
            'parent_id' => $request->parent_id,
            'root_id' => $rootId,
            'depth' => $depth,
            'content' => $request->content,
        ]);

        // Increment parent's replies count
        if ($parent) {
            $parent->incrementRepliesCount();
        }

        // Update page comments count
        $page->updateCommentsCount();
        Cache::forget("comments:page:{$page->id}");

        $comment->load(['user:id,name,email,avatar']);

        // Broadcast events
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

        $request->validate([
            'content' => 'required|string|min:1|max:5000',
        ]);

        $comment->update([
            'content' => $request->content,
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

        if (!$user) {
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
