<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MangaCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MangaCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MangaCategory::withCount('mangas')->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $categories = $query->paginate(15)->withQueryString();

        return Inertia::render('admin/category/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/category/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:manga_categories,slug'],
            'description' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['avatar'])) {
            unset($validated['avatar']);
        }

        MangaCategory::create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Category đã được tạo thành công.');
    }

    public function show(MangaCategory $category): Response
    {
        $category->loadCount('mangas');

        return Inertia::render('admin/category/Show', [
            'category' => $category,
        ]);
    }

    public function edit(MangaCategory $category): Response
    {
        return Inertia::render('admin/category/Edit', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, MangaCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:manga_categories,slug,'.$category->id],
            'description' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['avatar'])) {
            unset($validated['avatar']);
        } else {
            // If avatar changed, delete old one
            if ($category->avatar && $category->avatar !== $validated['avatar']) {
                \App\Jobs\DeleteImageStorageJob::dispatch([$category->avatar], []);
            }
        }

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Category đã được cập nhật thành công.');
    }

    public function destroy(MangaCategory $category): RedirectResponse
    {
        if ($category->avatar) {
            Storage::disk('public')->delete($category->avatar);
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category đã được xóa thành công.');
    }
}
