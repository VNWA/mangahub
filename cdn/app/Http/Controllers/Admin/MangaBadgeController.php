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

        return Inertia::render('Admin/Badge/Index', [
            'badges' => $badges,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Badge/Create');
    }

    public function store(Request $request): RedirectResponse
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

        return redirect()->route('badges.index')
            ->with('success', 'Badge đã được tạo thành công.');
    }

    public function show(MangaBadge $badge): Response
    {
        $badge->loadCount('mangas');

        return Inertia::render('Admin/Badge/Show', [
            'badge' => $badge,
        ]);
    }

    public function edit(MangaBadge $badge): Response
    {
        return Inertia::render('Admin/Badge/Edit', [
            'badge' => $badge,
        ]);
    }

    public function update(Request $request, MangaBadge $badge): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:manga_badges,slug,'.$badge->id],
            'light_text_color' => ['nullable', 'string', 'max:7'],
            'light_bg_color' => ['nullable', 'string', 'max:7'],
            'dark_text_color' => ['nullable', 'string', 'max:7'],
            'dark_bg_color' => ['nullable', 'string', 'max:7'],
        ]);

        $badge->update($validated);

        return redirect()->route('badges.index')
            ->with('success', 'Badge đã được cập nhật thành công.');
    }

    public function destroy(MangaBadge $badge): RedirectResponse
    {
        $badge->delete();

        return redirect()->route('badges.index')
            ->with('success', 'Badge đã được xóa thành công.');
    }
}
