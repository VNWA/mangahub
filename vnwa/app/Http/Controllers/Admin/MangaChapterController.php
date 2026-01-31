<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderMangaChapterRequest;
use App\Http\Requests\Admin\StoreFromUrlsMangaChapterRequest;
use App\Http\Requests\Admin\StoreMangaChapterRequest;
use App\Http\Requests\Admin\UpdateMangaChapterRequest;
use App\Http\Requests\Admin\UploadZipMangaChapterRequest;
use App\Models\Manga;
use App\Models\MangaChapter;
use App\Models\MangaServer;
use App\Models\ServerChapterContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;

class MangaChapterController extends Controller
{
    public function index(Request $request, Manga $manga): Response
    {
        $chapters = $manga->chapters()
            ->with(['user:id,name', 'serverContents.server:id,name'])
            ->orderBy('order')
            ->get();

        $servers = MangaServer::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Manga/Chapters/Index', [
            'manga' => $manga->load(['author:id,name', 'badge:id,name']),
            'chapters' => $chapters,
            'servers' => $servers,
        ]);
    }

    public function store(StoreMangaChapterRequest $request): JsonResponse
    {
        $manga = Manga::findOrFail($request->manga_id);
        $maxOrder = $manga->chapters()->max('order') ?? 0;

        $chapter = MangaChapter::create([
            'manga_id' => $request->manga_id,
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chapter đã được tạo thành công.',
            'chapter' => $chapter->load(['user:id,name']),
        ]);
    }

    public function update(UpdateMangaChapterRequest $request, MangaChapter $chapter): JsonResponse
    {
        $chapter->update($request->validated());

        // Update server contents if provided
        if ($request->has('server_contents') && is_array($request->server_contents)) {
            // Get all existing server contents for this chapter
            $existingContentIds = $chapter->serverContents()->pluck('id')->toArray();
            $updatedContentIds = [];

            foreach ($request->server_contents as $contentData) {
                if (! isset($contentData['manga_server_id']) || empty($contentData['manga_server_id'])) {
                    continue;
                }

                // Parse URLs from textarea (separated by newline or space)
                $urlsText = is_array($contentData['urls'])
                    ? implode("\n", $contentData['urls'])
                    : ($contentData['urls'] ?? '');
                $urls = preg_split('/[\s\n\r]+/', $urlsText);
                $urls = array_filter(array_map('trim', $urls), function ($url) {
                    return ! empty($url) && filter_var($url, FILTER_VALIDATE_URL);
                });

                if (empty($urls)) {
                    continue;
                }

                // Update existing or create new server content
                if (isset($contentData['id']) && in_array($contentData['id'], $existingContentIds)) {
                    // Update existing
                    $serverContent = ServerChapterContent::find($contentData['id']);
                    if ($serverContent && $serverContent->manga_chapter_id === $chapter->id) {
                        $serverContent->update([
                            'manga_server_id' => $contentData['manga_server_id'],
                            'urls' => array_values($urls),
                        ]);
                        $updatedContentIds[] = $serverContent->id;
                    }
                } else {
                    // Create new or update by server_id
                    $serverContent = ServerChapterContent::updateOrCreate(
                        [
                            'manga_server_id' => $contentData['manga_server_id'],
                            'manga_chapter_id' => $chapter->id,
                        ],
                        [
                            'urls' => array_values($urls),
                        ]
                    );
                    $updatedContentIds[] = $serverContent->id;
                }
            }

            // Delete server contents that were not updated
            $toDelete = array_diff($existingContentIds, $updatedContentIds);
            if (! empty($toDelete)) {
                ServerChapterContent::whereIn('id', $toDelete)
                    ->where('manga_chapter_id', $chapter->id)
                    ->delete();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Chapter đã được cập nhật thành công.',
            'chapter' => $chapter->fresh(['user:id,name', 'serverContents.server:id,name']),
        ]);
    }

    public function destroy(MangaChapter $chapter): JsonResponse
    {
        // Xóa các file ảnh từ storage trước khi xóa chapter
        $serverContents = $chapter->serverContents;
        foreach ($serverContents as $content) {
            if (is_array($content->urls)) {
                foreach ($content->urls as $url) {
                    // Kiểm tra xem URL có phải là storage URL không
                    $storagePath = $this->extractStoragePath($url);
                    if ($storagePath && Storage::disk('public')->exists($storagePath)) {
                        Storage::disk('public')->delete($storagePath);
                    }
                }
            }
        }

        // Xóa thư mục chapter nếu tồn tại
        $chapterDir = 'mangas/'.$chapter->manga_id.'/chapters/'.$chapter->slug;
        if (Storage::disk('public')->exists($chapterDir)) {
            Storage::disk('public')->deleteDirectory($chapterDir);
        }

        $chapter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chapter đã được xóa thành công.',
        ]);
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

    public function reorder(ReorderMangaChapterRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            foreach ($request->chapters as $item) {
                MangaChapter::where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Thứ tự chapters đã được cập nhật thành công.',
        ]);
    }

    public function uploadZip(UploadZipMangaChapterRequest $request): JsonResponse
    {
        try {
            $zipFile = $request->file('zip_file');
            $manga = Manga::findOrFail($request->manga_id);

            // Lấy tên chapter từ tên file (bỏ extension .zip)
            $chapterName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);

            // Tạo slug từ tên chapter
            $slug = Str::slug($chapterName);
            $uniqueSlug = $slug;
            $counter = 1;
            while (MangaChapter::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = $slug.'-'.$counter;
                $counter++;
            }

            // Giải nén file ZIP
            $zip = new ZipArchive;
            $zipPath = $zipFile->getRealPath();

            if ($zip->open($zipPath) !== true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể mở file ZIP.',
                ], 422);
            }

            // Tạo thư mục lưu trữ
            $storagePath = 'mangas/'.$manga->id.'/chapters/'.$uniqueSlug;

            // Lấy server đầu tiên hoặc tạo default server nếu chưa có
            $server = MangaServer::orderBy('id')->first();
            if (! $server) {
                $server = MangaServer::create([
                    'name' => 'Server 1',
                    'description' => 'Default manga server',
                ]);
            }

            // Giải nén và lưu ảnh
            $imageUrls = [];
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Bỏ qua thư mục
                if (substr($filename, -1) === '/') {
                    continue;
                }

                // Kiểm tra extension
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (! in_array($ext, $imageExtensions)) {
                    continue;
                }

                // Lấy tên file
                $basename = basename($filename);
                $filePath = $storagePath.'/'.$basename;

                // Lưu file từ ZIP vào storage
                $fileContent = $zip->getFromIndex($i);
                Storage::disk('public')->put($filePath, $fileContent);

                // Lưu URL
                $imageUrls[] = Storage::disk('public')->url($filePath);
            }

            $zip->close();

            if (empty($imageUrls)) {
                // Xóa thư mục nếu không có ảnh
                Storage::disk('public')->deleteDirectory($storagePath);

                return response()->json([
                    'success' => false,
                    'message' => 'File ZIP không chứa ảnh hợp lệ.',
                ], 422);
            }

            // Sắp xếp URLs theo tên file
            sort($imageUrls);

            // Tạo chapter
            $maxOrder = $manga->chapters()->max('order') ?? 0;
            $chapter = MangaChapter::create([
                'manga_id' => $manga->id,
                'user_id' => auth()->id(),
                'name' => $chapterName,
                'slug' => $uniqueSlug,
                'order' => $maxOrder + 1,
            ]);

            // Tạo server chapter content
            ServerChapterContent::create([
                'manga_server_id' => $server->id,
                'manga_chapter_id' => $chapter->id,
                'urls' => $imageUrls,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chapter đã được upload thành công từ file ZIP.',
                'chapter' => $chapter->load(['user:id,name', 'serverContents.server:id,name']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: '.$e->getMessage(),
            ], 500);
        }
    }

    public function storeFromUrls(StoreFromUrlsMangaChapterRequest $request): JsonResponse
    {
        try {
            $manga = Manga::findOrFail($request->manga_id);

            // Parse URLs từ textarea (ngăn cách bởi xuống dòng hoặc khoảng trắng)
            $urlsText = $request->urls;
            $urls = preg_split('/[\s\n\r]+/', $urlsText);
            $urls = array_filter(array_map('trim', $urls), function ($url) {
                return ! empty($url) && filter_var($url, FILTER_VALIDATE_URL);
            });

            if (empty($urls)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có URL hợp lệ.',
                ], 422);
            }

            // Tạo slug từ tên chapter
            $slug = Str::slug($request->name);
            $uniqueSlug = $slug;
            $counter = 1;
            while (MangaChapter::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = $slug.'-'.$counter;
                $counter++;
            }

            // Lấy server đầu tiên hoặc tạo default server nếu chưa có
            $server = MangaServer::orderBy('id')->first();
            if (! $server) {
                $server = MangaServer::create([
                    'name' => 'Server 1',
                    'description' => 'Default manga server',
                ]);
            }

            // Tạo chapter
            $maxOrder = $manga->chapters()->max('order') ?? 0;
            $chapter = MangaChapter::create([
                'manga_id' => $manga->id,
                'user_id' => auth()->id(),
                'name' => $request->name,
                'slug' => $uniqueSlug,
                'description' => $request->description,
                'order' => $maxOrder + 1,
            ]);

            // Tạo server chapter content
            ServerChapterContent::create([
                'manga_server_id' => $server->id,
                'manga_chapter_id' => $chapter->id,
                'urls' => array_values($urls),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chapter đã được tạo thành công từ URLs.',
                'chapter' => $chapter->load(['user:id,name', 'serverContents.server:id,name']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: '.$e->getMessage(),
            ], 500);
        }
    }
}
