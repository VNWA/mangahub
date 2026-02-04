<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Comment::with(['user:id,name,email', 'page' => function ($q) {
            $q->with('pageable:id,name,slug');
        }])
            ->whereNull('parent_id')
            ->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('is_pinned')) {
            $query->where('is_pinned', $request->is_pinned === 'true');
        }

        if ($request->has('pageable_type')) {
            $query->whereHas('page', function ($pageQuery) use ($request) {
                $pageQuery->where('pageable_type', $request->pageable_type);
            });
        }

        $comments = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Comment/Index', [
            'comments' => $comments,
            'filters' => $request->only(['search', 'is_pinned', 'pageable_type']),
        ]);
    }

    public function show(Comment $comment): Response
    {
        $comment->load([
            'user:id,name,email,avatar',
            'page' => function ($q) {
                $q->with('pageable:id,name,slug');
            },
            'parent:id,content,user_id',
            'replies.user:id,name,email,avatar',
            'reactions.user:id,name',
        ]);

        return Inertia::render('Admin/Comment/Show', [
            'comment' => $comment,
        ]);
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $comment->update([
            'content' => $validated['content'],
            'is_edited' => true,
        ]);

        return redirect()->back()
            ->with('success', 'Comment đã được cập nhật thành công.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return redirect()->route('comments.index')
            ->with('success', 'Comment đã được xóa thành công.');
    }

    public function pin(Comment $comment): RedirectResponse
    {
        $comment->update(['is_pinned' => true]);

        return redirect()->back()
            ->with('success', 'Comment đã được ghim.');
    }

    public function unpin(Comment $comment): RedirectResponse
    {
        $comment->update(['is_pinned' => false]);

        return redirect()->back()
            ->with('success', 'Comment đã được bỏ ghim.');
    }
}
