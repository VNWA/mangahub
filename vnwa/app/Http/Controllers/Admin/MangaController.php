<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Image as ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMangaRequest;
use App\Http\Requests\Admin\UpdateMangaRequest;
use App\Jobs\DeleteImageStorageJob;
use App\Models\Manga;
use App\Models\MangaAuthor;
use App\Models\MangaBadge;
use App\Models\MangaCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            $data['avatar'] = $this->storeResizedAvatar($request->file('avatar'));
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
            // Lưu avatar cũ để xóa sau
            $oldAvatar = $manga->avatar;

            // Upload avatar mới (resize theo config + random name)
            $data['avatar'] = $this->storeResizedAvatar($request->file('avatar'));

            // Xóa avatar cũ bằng job
            if ($oldAvatar) {
                DeleteImageStorageJob::dispatch([$oldAvatar], []);
            }
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
        // Thu thập tất cả URLs từ chapters và avatar
        $urls = [];

        // Thêm avatar nếu có
        if ($manga->avatar) {
            $urls[] = $manga->avatar;
        }

        // Thu thập URLs từ tất cả chapters
        foreach ($manga->chapters as $chapter) {
            foreach ($chapter->serverContents as $content) {
                if (is_array($content->urls)) {
                    $urls = array_merge($urls, $content->urls);
            }
        }
        }

        $mangaId = $manga->id;

        // Xóa manga trước
        $manga->delete();

        // Thu thập các thư mục cần xóa
        $directories = [];
        foreach ($manga->chapters as $chapter) {
            $directories[] = "mangas/{$mangaId}/chapters/{$chapter->slug}";
        }
        $directories[] = "mangas/{$mangaId}";

        // Dispatch job để xóa ảnh bất đồng bộ
        \App\Jobs\DeleteImageStorageJob::dispatch($urls, $directories);

        return redirect()->route('mangas.index')
            ->with('success', 'Manga đã được xóa thành công. Các file ảnh sẽ được xóa trong background.');
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

    private function storeResizedAvatar(\Illuminate\Http\UploadedFile $file): string
    {
        $avatarConfig = (array) config('vnwa.manga.avatar', []);

        $width = $avatarConfig['width'] ?? $avatarConfig['with'] ?? null;
        $height = $avatarConfig['height'] ?? null;
        $format = $avatarConfig['format'] ?? 'webp';
        $quality = (int) ($avatarConfig['quality'] ?? 90);

        // Xác định disk để upload (MinIO hoặc default)
        $defaultDisk = config('filesystems.default', 'local');
        $storageDisk = in_array($defaultDisk, ['minio', 's3']) ? $defaultDisk : 'public';
        $storage = Storage::disk($storageDisk);

        // Tạo file tạm để resize
        $tempPath = $file->getRealPath();
        $tempDir = sys_get_temp_dir();
        $tempFileName = Str::random(40).'.'.$format;
        $tempFilePath = $tempDir.'/'.$tempFileName;

        try {
            // Resize và convert ảnh vào file tạm
            ImageHelper::convert(
                source: $tempPath,
                target: $tempFilePath,
                width: is_numeric($width) ? (int) $width : null,
                height: is_numeric($height) ? (int) $height : null,
                extension: (string) $format,
                quality: $quality,
            );

            // Upload file đã resize lên storage (MinIO hoặc local)
            $directory = 'mangas/avatars';
            $randomName = Str::random(40).'.'.$format;
            $relativePath = $directory.'/'.$randomName;

            // Đọc file đã resize và upload lên storage
            $resizedContent = file_get_contents($tempFilePath);
            $storage->put($relativePath, $resizedContent);

            return $relativePath;
        } finally {
            // Xóa file tạm nếu tồn tại
            if (file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }
    }
}
