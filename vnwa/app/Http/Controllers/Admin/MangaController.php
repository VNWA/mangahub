<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMangaRequest;
use App\Http\Requests\Admin\UpdateMangaRequest;
use App\Models\Manga;
use App\Models\MangaAuthor;
use App\Models\MangaBadge;
use App\Models\MangaCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MangaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Manga::with(['author:id,name', 'badge:id,name', 'user:id,name', 'categories:id,name'])
            ->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $mangas = $query->paginate(15)->withQueryString();

        $authors = MangaAuthor::orderBy('name')->get(['id', 'name']);
        $badges = MangaBadge::orderBy('name')->get(['id', 'name']);
        $categories = MangaCategory::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Manga/Index', [
            'mangas' => $mangas,
            'authors' => $authors,
            'badges' => $badges,
            'categories' => $categories,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        $authors = MangaAuthor::orderBy('name')->get(['id', 'name']);
        $badges = MangaBadge::orderBy('name')->get(['id', 'name']);
        $categories = MangaCategory::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Manga/Create', [
            'authors' => $authors,
            'badges' => $badges,
            'categories' => $categories,
        ]);
    }

    public function store(StoreMangaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('mangas/avatars', 'public');
        }

        $manga = Manga::create($data);

        if ($request->has('categories') && is_array($request->categories)) {
            $manga->categories()->sync($request->categories);
        }

        return redirect()->route('mangas.index')
            ->with('success', 'Manga đã được tạo thành công.');
    }

    public function show(Manga $manga): Response
    {
        $manga->load(['author', 'badge', 'user', 'categories', 'chapters']);

        return Inertia::render('Admin/Manga/Show', [
            'manga' => $manga,
        ]);
    }

    public function edit(Manga $manga): Response
    {
        $manga->load('categories');
        $authors = MangaAuthor::orderBy('name')->get(['id', 'name']);
        $badges = MangaBadge::orderBy('name')->get(['id', 'name']);
        $categories = MangaCategory::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Manga/Edit', [
            'manga' => $manga,
            'authors' => $authors,
            'badges' => $badges,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateMangaRequest $request, Manga $manga): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($manga->avatar) {
                Storage::disk('public')->delete($manga->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('mangas/avatars', 'public');
        }

        $manga->update($data);

        if ($request->has('categories') && is_array($request->categories)) {
            $manga->categories()->sync($request->categories);
        } else {
            $manga->categories()->detach();
        }

        return redirect()->route('mangas.index')
            ->with('success', 'Manga đã được cập nhật thành công.');
    }

    public function destroy(Manga $manga): RedirectResponse
    {
        // Xóa avatar nếu có
        if ($manga->avatar) {
            $avatarPath = $this->extractStoragePath($manga->avatar);
            if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }
        }

        // Xóa tất cả chapters và ảnh liên quan
        foreach ($manga->chapters as $chapter) {
            // Xóa server contents và ảnh
            foreach ($chapter->serverContents as $content) {
                if (is_array($content->urls)) {
                    foreach ($content->urls as $url) {
                        $storagePath = $this->extractStoragePath($url);
                        if ($storagePath && Storage::disk('public')->exists($storagePath)) {
                            Storage::disk('public')->delete($storagePath);
                        }
                    }
                }
            }

            // Xóa thư mục chapter
            $chapterDir = 'mangas/'.$manga->id.'/chapters/'.$chapter->slug;
            if (Storage::disk('public')->exists($chapterDir)) {
                Storage::disk('public')->deleteDirectory($chapterDir);
            }
        }

        // Xóa thư mục manga nếu tồn tại
        $mangaDir = 'mangas/'.$manga->id;
        if (Storage::disk('public')->exists($mangaDir)) {
            Storage::disk('public')->deleteDirectory($mangaDir);
        }

        $manga->delete();

        return redirect()->route('mangas.index')
            ->with('success', 'Manga đã được xóa thành công.');
    }

    /**
     * Extract storage path from URL
     */
    private function extractStoragePath(string $url): ?string
    {
        // Nếu URL là storage URL (chứa /storage/)
        if (str_contains($url, '/storage/')) {
            $parts = explode('/storage/', $url);
            if (isset($parts[1])) {
                return $parts[1];
            }
        }

        // Nếu URL đã là path tương đối (không có domain)
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            // Loại bỏ leading slash nếu có
            return ltrim($url, '/');
        }

        return null;
    }
}
