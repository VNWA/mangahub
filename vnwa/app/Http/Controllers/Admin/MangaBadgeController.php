<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MangaBadge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MangaBadgeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MangaBadge::withCount('mangas')->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $badges = $query->paginate(15)->withQueryString();

        // Add isSystemBadge flag to each badge
        $badges->getCollection()->transform(function ($badge) {
            $badge->is_system_badge = $badge->isSystemBadge();

            return $badge;
        });

        return Inertia::render('admin/badge/Index', [
            'badges' => $badges,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/badge/Create');
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:manga_badges,slug'],
            'light_text_color' => ['nullable', 'string', 'max:7'],
            'light_bg_color' => ['nullable', 'string', 'max:7'],
            'dark_text_color' => ['nullable', 'string', 'max:7'],
            'dark_bg_color' => ['nullable', 'string', 'max:7'],
        ]);

        MangaBadge::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Badge đã được tạo thành công.',
            ]);
        }

        return redirect()->route('badges.index')
            ->with('success', 'Badge đã được tạo thành công.');
    }

    public function show(MangaBadge $badge): Response
    {
        $badge->loadCount('mangas');

        return Inertia::render('admin/badge/Show', [
            'badge' => $badge,
            'isSystemBadge' => $badge->isSystemBadge(),
        ]);
    }

    public function edit(MangaBadge $badge): Response
    {
        return Inertia::render('admin/badge/Edit', [
            'badge' => $badge,
            'isSystemBadge' => $badge->isSystemBadge(),
        ]);
    }

    public function update(Request $request, MangaBadge $badge): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:manga_badges,slug,'.$badge->id],
            'light_text_color' => ['nullable', 'string', 'max:7'],
            'light_bg_color' => ['nullable', 'string', 'max:7'],
            'dark_text_color' => ['nullable', 'string', 'max:7'],
            'dark_bg_color' => ['nullable', 'string', 'max:7'],
        ]);

        // Prevent updating slug for system badges
        if ($badge->isSystemBadge()) {
            unset($validated['slug']);
        }

        $badge->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Badge đã được cập nhật thành công.',
            ]);
        }

        return redirect()->route('badges.index')
            ->with('success', 'Badge đã được cập nhật thành công.');
    }

    public function destroy(Request $request, MangaBadge $badge): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        // Prevent deleting system badges
        if ($badge->isSystemBadge()) {
            $message = 'Không thể xóa badge hệ thống này.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return redirect()->route('badges.index')
                ->with('error', $message);
        }

        $badge->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Badge đã được xóa thành công.',
            ]);
        }

        return redirect()->route('badges.index')
            ->with('success', 'Badge đã được xóa thành công.');
    }
}
