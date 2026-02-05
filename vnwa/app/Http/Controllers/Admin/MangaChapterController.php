<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderMangaChapterRequest;
use App\Http\Requests\Admin\StoreFromUrlsMangaChapterRequest;
use App\Http\Requests\Admin\StoreMangaChapterRequest;
use App\Http\Requests\Admin\UpdateMangaChapterRequest;
use App\Models\Manga;
use App\Models\MangaChapter;
use App\Models\MangaServer;
use App\Models\ServerChapterContent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RahulHaque\Filepond\Facades\Filepond;
use ZipArchive;

class MangaChapterController extends Controller
{
    public function index(Request $request, Manga $manga): Response
    {
        $chapters = MangaChapter::where('manga_id', $manga->id)
            ->with(['user:id,name', 'serverContents.server:id,name'])
            ->orderBy('order', 'desc')
            ->get();

        $servers = MangaServer::orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/manga/chapters/Index', [
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

    public function update(UpdateMangaChapterRequest $request, Manga $manga, MangaChapter $chapter): JsonResponse
    {
        // Kiểm tra chapter thuộc về manga
        if ($chapter->manga_id !== $manga->id) {
            return response()->json([
                'success' => false,
                'message' => 'Chapter không thuộc về manga này.',
            ], 422);
        }

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

    public function destroy(Manga $manga, MangaChapter $chapter): JsonResponse
    {
        // Kiểm tra chapter thuộc về manga
        if ($chapter->manga_id !== $manga->id) {
            return response()->json([
                'success' => false,
                'message' => 'Chapter không thuộc về manga này.',
            ], 422);
        }

        // Load server contents trước khi xóa
        $chapter->load('serverContents');

        // Thu thập tất cả URLs từ server contents
        $urls = [];
        foreach ($chapter->serverContents as $content) {
            if (is_array($content->urls)) {
                $urls = array_merge($urls, $content->urls);
            }
        }

        $mangaId = $chapter->manga_id;
        $chapterSlug = $chapter->slug;

        // Xóa chapter (sẽ tự động xóa server contents do cascade)
        $chapter->delete();

        // Dispatch job để xóa ảnh bất đồng bộ
        $directories = [];
        if ($mangaId && $chapterSlug) {
            $directories[] = "mangas/{$mangaId}/chapters/{$chapterSlug}";
        }

        if (! empty($urls) || ! empty($directories)) {
            \App\Jobs\DeleteImageStorageJob::dispatch($urls, $directories);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chapter đã được xóa thành công. Các file ảnh sẽ được xóa trong background.',
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

    public function uploadZip(Request $request): JsonResponse
    {
        $zipFile = $request->file('zip_file');
        $folderName = 'salaries-'.Carbon::now()->format('F').'-'.rand(1000, 9999);
        $zipFilePath = $zipFile->path();
        Storage::makeDirectory($folderName);
        Storage::put($folderName.'/'.$zipFile->getClientOriginalName(), file_get_contents($zipFile));

        return response()->json([
            'success' => true,
            'message' => 'File đã được upload thành công.',
            'file_path' => $zipFilePath,
        ]);
    }

    /**
     * Upload nhiều ZIP files từ FilePond serverIds
     */
    public function uploadMultipleZip(Request $request, Manga $manga): JsonResponse
    {
        $request->validate([
            'server_ids' => ['required', 'array', 'min:1', 'max:10'],
            'server_ids.*' => ['required', 'string'],
        ]);

        $storage = Storage::disk('minio');

        $chapters = [];
        $errors = [];
        $serverIds = $request->input('server_ids', []);

        // Lấy server đầu tiên hoặc tạo default server nếu chưa có
        $server = MangaServer::orderBy('id')->first();
        if (! $server) {
            $server = MangaServer::create([
                'name' => 'Server 1',
                'description' => 'Default manga server',
            ]);
        }

        // Lấy max order hiện tại
        $maxOrder = $manga->chapters()->max('order') ?? 0;

        foreach ($serverIds as $index => $serverId) {
            $zipPath = null;
            $uploadedFile = null;
            try {
                // Lấy file từ FilePond - FilePond đã lưu vào local disk temp
                // getFile() trả về UploadedFile object
                $uploadedFile = Filepond::field($serverId)->getFile();

                if (! $uploadedFile || ! ($uploadedFile instanceof \Illuminate\Http\UploadedFile)) {
                    $errors[] = "File #{$index}: Không thể lấy file từ FilePond với server_id: {$serverId}";
                    \Log::error('FilePond getFile failed', [
                        'server_id' => $serverId,
                        'file_type' => gettype($uploadedFile),
                    ]);

                    continue;
                }

                // Lấy path từ UploadedFile object
                // getRealPath() trả về đường dẫn tuyệt đối của file tạm
                $zipPath = $uploadedFile->getRealPath();

                if (! $zipPath) {
                    // Fallback: lấy path từ getPathname()
                    $zipPath = $uploadedFile->getPathname();
                }

                // Kiểm tra file có tồn tại không
                if (! file_exists($zipPath)) {
                    $errors[] = "File #{$index}: Không tìm thấy file tại: {$zipPath}";
                    \Log::error('ZIP file not found', [
                        'server_id' => $serverId,
                        'zip_path' => $zipPath,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);

                    continue;
                }

                // Kiểm tra file có phải là ZIP không
                $mimeType = mime_content_type($zipPath);
                if (! is_file($zipPath) || ! in_array($mimeType, ['application/zip', 'application/x-zip-compressed'])) {
                    $errors[] = "File #{$index}: File không phải là ZIP hợp lệ (mime: {$mimeType}).";
                    \Log::warning('Invalid ZIP file', [
                        'server_id' => $serverId,
                        'zip_path' => $zipPath,
                        'mime_type' => $mimeType,
                    ]);

                    continue;
                }

                // Lấy tên file ZIP gốc (bỏ extension)
                $originalFileName = $uploadedFile->getClientOriginalName();
                $zipFileName = pathinfo($originalFileName, PATHINFO_FILENAME);

                // Process ZIP file
                $result = $this->processZipFile($zipPath, $manga, $server, $storage, $maxOrder, $zipFileName);

                if ($result['success']) {
                    $chapters[] = $result['chapter'];
                    $maxOrder = $result['chapter']->order;
                } else {
                    $errors[] = "File #{$index}: {$result['message']}";
                }
            } catch (\Exception $e) {
                $errors[] = "File #{$index}: {$e->getMessage()}";
                \Log::error('Upload multiple ZIP error', [
                    'server_id' => $serverId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'zip_path' => $zipPath ?? 'N/A',
                ]);
            } finally {
                // Xóa file từ FilePond (sẽ tự xóa temp file)
                try {
                    Filepond::field($serverId)->delete();
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete FilePond temp file', [
                        'server_id' => $serverId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (empty($chapters) && ! empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể upload bất kỳ chapter nào.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã upload thành công '.count($chapters).' chapter(s).',
            'chapters' => $chapters,
            'errors' => $errors,
        ]);
    }

    /**
     * Process một ZIP file thành chapter
     */
    private function processZipFile(string $zipPath, Manga $manga, MangaServer $server, $storage, int $startOrder, ?string $zipFileName = null): array
    {
        $zip = null;
        $uploadedFiles = [];

        try {
            // Lấy tên chapter từ tên file ZIP gốc (nếu có), nếu không thì lấy từ zipPath
            if ($zipFileName) {
                $chapterName = $zipFileName;
            } else {
                // Fallback: lấy từ tên file (bỏ extension .zip)
                $chapterName = pathinfo(basename($zipPath), PATHINFO_FILENAME);
            }

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
            if ($zip->open($zipPath) !== true) {
                return [
                    'success' => false,
                    'message' => 'Không thể mở file ZIP.',
                ];
            }

            // Tạo thư mục lưu trữ
            $storagePath = 'mangas/'.$manga->id.'/chapters/'.$uniqueSlug;

            // Giải nén và lưu ảnh
            $imageFiles = [];
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

                // Lưu thông tin file với thứ tự
                $imageFiles[] = [
                    'index' => $i,
                    'original_name' => $filename,
                    'extension' => $ext,
                ];
            }

            // Sắp xếp theo tên file gốc để giữ thứ tự
            usort($imageFiles, function ($a, $b) {
                return strnatcasecmp($a['original_name'], $b['original_name']);
            });

            $imageUrls = [];
            foreach ($imageFiles as $fileInfo) {
                // Tạo random name cho file để tránh trùng
                $randomName = Str::random(40).'.'.$fileInfo['extension'];
                $filePath = $storagePath.'/'.$randomName;

                // Lưu file từ ZIP vào storage
                $fileContent = $zip->getFromIndex($fileInfo['index']);
                $storage->put($filePath, $fileContent);
                $uploadedFiles[] = $filePath;

                // Lưu URL
                $imageUrls[] = $filePath;
            }

            $zip->close();
            $zip = null;

            if (empty($imageUrls)) {
                // Xóa các file đã upload nếu không có ảnh hợp lệ
                $this->cleanupUploadedFiles($storage, $uploadedFiles, $storagePath);

                return [
                    'success' => false,
                    'message' => 'File ZIP không chứa ảnh hợp lệ.',
                ];
            }

            // Tạo chapter
            $chapter = MangaChapter::create([
                'manga_id' => $manga->id,
                'user_id' => auth()->id(),
                'name' => $chapterName,
                'slug' => $uniqueSlug,
                'order' => $startOrder + 1,
            ]);

            // Tạo server chapter content
            ServerChapterContent::create([
                'manga_server_id' => $server->id,
                'manga_chapter_id' => $chapter->id,
                'urls' => $imageUrls,
            ]);

            return [
                'success' => true,
                'chapter' => $chapter->load(['user:id,name', 'serverContents.server:id,name']),
            ];
        } catch (\Exception $e) {
            // Cleanup uploaded files nếu có lỗi
            if (! empty($uploadedFiles)) {
                $this->cleanupUploadedFiles($storage, $uploadedFiles, $storagePath ?? '');
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } finally {
            // Đảm bảo ZIP được đóng
            if ($zip !== null) {
                @$zip->close();
            }
        }
    }

    /**
     * Cleanup uploaded files khi có lỗi
     */
    private function cleanupUploadedFiles($storage, array $uploadedFiles, string $storagePath): void
    {
        try {
            // Xóa từng file đã upload
            foreach ($uploadedFiles as $filePath) {
                try {
                    $storage->delete($filePath);
                } catch (\Exception $e) {
                    // Ignore individual file deletion errors
                }
            }

            // Với local storage, có thể xóa directory
            // Với S3/MinIO, directory tự động xóa khi không còn file
            // Kiểm tra xem có phải local disk không bằng cách thử deleteDirectory
            try {
                $storage->deleteDirectory($storagePath);
            } catch (\Exception $e) {
                // Ignore directory deletion errors (S3/MinIO không support deleteDirectory)
            }
        } catch (\Exception $e) {
            // Ignore cleanup errors
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
