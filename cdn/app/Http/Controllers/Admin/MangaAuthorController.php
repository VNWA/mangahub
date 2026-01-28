<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MangaAuthor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MangaAuthorController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MangaAuthor::withCount('mangas')->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $authors = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Author/Index', [
            'authors' => $authors,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Author/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:manga_authors,slug'],
            'description' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('authors/avatars', 'public');
        }

        MangaAuthor::create($validated);

        return redirect()->route('authors.index')
            ->with('success', 'Author đã được tạo thành công.');
    }

    public function show(MangaAuthor $author): Response
    {
        $author->loadCount('mangas');

        return Inertia::render('Admin/Author/Show', [
            'author' => $author,
        ]);
    }

    public function edit(MangaAuthor $author): Response
    {
        return Inertia::render('Admin/Author/Edit', [
            'author' => $author,
        ]);
    }

    public function update(Request $request, MangaAuthor $author): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:manga_authors,slug,'.$author->id],
            'description' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($author->avatar) {
                Storage::disk('public')->delete($author->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('authors/avatars', 'public');
        }

        $author->update($validated);

        return redirect()->route('authors.index')
            ->with('success', 'Author đã được cập nhật thành công.');
    }

    public function destroy(MangaAuthor $author): RedirectResponse
    {
        if ($author->avatar) {
            Storage::disk('public')->delete($author->avatar);
        }

        $author->delete();

        return redirect()->route('authors.index')
            ->with('success', 'Author đã được xóa thành công.');
    }
}
