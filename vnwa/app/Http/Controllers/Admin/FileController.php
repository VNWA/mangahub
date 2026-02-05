<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RahulHaque\Filepond\Facades\Filepond;
use ZipArchive;
use App\Helpers\Image as ImageHelper;

class FileController extends Controller
{





    public function index(Request $request): Response
    {
        $path = $request->get('path', '');
        $files = $this->listFiles($path);

        return Inertia::render('admin/file/Index', [
            'files' => $files,
            'currentPath' => $path,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $path = $request->get('path', '') ?? '';
        $files = $this->listFiles($path);

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'nullable|string',
        ]);

        $path = $request->get('path', '');
        $folderName = $this->formatFileName($request->name);
        $fullPath = $path ? "{$path}/{$folderName}" : $folderName;

        if (Storage::exists($fullPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Thư mục đã tồn tại.',
            ], 422);
        }

        Storage::makeDirectory($fullPath);

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo thư mục thành công.',
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        // Giữ lại method cũ để backward compatibility
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file',
            'path' => 'nullable|string',
        ]);

        $path = $request->get('path', '');
        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
            $formattedName = $this->formatFileName($nameWithoutExt);
            $fileName = $extension ? "{$formattedName}.{$extension}" : $formattedName;
            $filePath = $path ? "{$path}/{$fileName}" : $fileName;

            // Nếu file đã tồn tại, thêm timestamp
            if (Storage::exists($filePath)) {
                $filePath = $path ? "{$path}/{$formattedName}_".time().".{$extension}" : "{$formattedName}_".time().".{$extension}";
            }

            $storedPath = $file->storeAs($path ?: '', basename($filePath));
            $uploadedFiles[] = $storedPath;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã upload thành công '.count($uploadedFiles).' file(s).',
            'files' => $uploadedFiles,
        ]);
    }

    /**
     * Upload files từ FilePond serverIds
     */
    public function uploadFromFilePond(Request $request): JsonResponse
    {
        $request->validate([
            'server_ids' => ['required', 'array', 'min:1'],
            'server_ids.*' => ['required', 'string'],
            'path' => ['nullable', 'string'],
        ]);

        $path = $request->get('path', '');
        $serverIds = $request->input('server_ids', []);
        $uploadedFiles = [];
        $errors = [];

        foreach ($serverIds as $index => $serverId) {
            $uploadedFile = null;
            try {
                // Lấy file từ FilePond
                $uploadedFile = Filepond::field($serverId)->getFile();

                if (! $uploadedFile || ! ($uploadedFile instanceof \Illuminate\Http\UploadedFile)) {
                    $errors[] = "File #{$index}: Không thể lấy file từ FilePond.";
                    \Log::error('FilePond getFile failed', [
                        'server_id' => $serverId,
                        'file_type' => gettype($uploadedFile),
                    ]);

                    continue;
                }

                // Format tên file
                $originalName = $uploadedFile->getClientOriginalName();
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                $formattedName = $this->formatFileName($nameWithoutExt);
                $fileName = $extension ? "{$formattedName}.{$extension}" : $formattedName;
                $filePath = $path ? "{$path}/{$fileName}" : $fileName;

                // Nếu file đã tồn tại, thêm timestamp
                if (Storage::exists($filePath)) {
                    $filePath = $path ? "{$path}/{$formattedName}_".time().".{$extension}" : "{$formattedName}_".time().".{$extension}";
                }

                // Lưu file vào storage
                $storedPath = $uploadedFile->storeAs($path ?: '', basename($filePath));
                $uploadedFiles[] = $storedPath;

                \Log::info('File uploaded successfully', [
                    'server_id' => $serverId,
                    'stored_path' => $storedPath,
                ]);
            } catch (\Exception $e) {
                $errors[] = "File #{$index}: {$e->getMessage()}";
                \Log::error('FilePond upload error', [
                    'server_id' => $serverId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } finally {
                // Xóa temp file từ FilePond (sẽ tự xóa temp file)
                try {
                    if ($serverId) {
                        Filepond::field($serverId)->delete();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete FilePond temp file', [
                        'server_id' => $serverId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (empty($uploadedFiles) && ! empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể upload bất kỳ file nào.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã upload thành công '.count($uploadedFiles).' file(s).',
            'files' => $uploadedFiles,
            'errors' => $errors,
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
        ]);

        $deleted = 0;
        $errors = [];

        foreach ($request->paths as $filePath) {
            try {
                if (Storage::exists($filePath)) {
                    // Check if it's a directory by trying to list it
                    try {
                        $files = Storage::files($filePath);
                        $directories = Storage::directories($filePath);
                        if (count($files) > 0 || count($directories) > 0) {
                            Storage::deleteDirectory($filePath);
                        } else {
                            // Empty directory or file
                            if (Storage::exists($filePath)) {
                                Storage::delete($filePath);
                            }
                        }
                    } catch (\Exception $e) {
                        // If listing fails, it's likely a file
                        Storage::delete($filePath);
                    }
                    $deleted++;
                }
            } catch (\Exception $e) {
                $errors[] = "Không thể xóa {$filePath}: ".$e->getMessage();
            }
        }

        return response()->json([
            'success' => $deleted > 0,
            'message' => "Đã xóa {$deleted} item(s).",
            'errors' => $errors,
        ]);
    }

    public function rename(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'newName' => 'required|string|max:255',
        ]);

        $oldPath = $request->path;
        $originalName = $request->newName;

        // Check if it's a file or folder
        $allDirs = Storage::directories(dirname($oldPath) !== '.' ? dirname($oldPath) : '');
        $isFile = ! in_array($oldPath, $allDirs);

        if ($isFile) {
            // For files, preserve original extension if new name doesn't have one
            $originalExtension = pathinfo($oldPath, PATHINFO_EXTENSION);
            $newNameExtension = pathinfo($originalName, PATHINFO_EXTENSION);

            // Nếu tên mới không có extension, dùng extension gốc
            if (empty($newNameExtension) && ! empty($originalExtension)) {
                $nameWithoutExt = $originalName;
                $formattedName = $this->formatFileName($nameWithoutExt);
                $newName = "{$formattedName}.{$originalExtension}";
            } else {
                // Nếu có extension trong tên mới, dùng extension đó
                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                $formattedName = $this->formatFileName($nameWithoutExt);
                $fileExtension = $newNameExtension ?: $originalExtension;
                $newName = $fileExtension ? "{$formattedName}.{$fileExtension}" : $formattedName;
            }
        } else {
            // For folders, just format the name
            $newName = $this->formatFileName($originalName);
        }

        $directory = dirname($oldPath);
        $newPath = $directory !== '.' ? "{$directory}/{$newName}" : $newName;

        if (! Storage::exists($oldPath)) {
            return response()->json([
                'success' => false,
                'message' => 'File/Thư mục không tồn tại.',
            ], 404);
        }

        if (Storage::exists($newPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Tên mới đã tồn tại.',
            ], 422);
        }

        // Move file/folder
        Storage::move($oldPath, $newPath);

        return response()->json([
            'success' => true,
            'message' => 'Đã đổi tên thành công.',
        ]);
    }

    public function extract(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'destination' => 'nullable|string',
        ]);

        $filePath = $request->path;
        $destination = $request->get('destination', dirname($filePath));

        if (! Storage::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File không tồn tại.',
            ], 404);
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension !== 'zip') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ hỗ trợ giải nén file ZIP.',
            ], 422);
        }

        try {
            $fullPath = Storage::path($filePath);
            $extractPath = Storage::path($destination);

            $zip = new ZipArchive;
            if ($zip->open($fullPath) === true) {
                $zip->extractTo($extractPath);
                $zip->close();

                return response()->json([
                    'success' => true,
                    'message' => 'Đã giải nén thành công.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không thể mở file ZIP.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi giải nén: '.$e->getMessage(),
            ], 500);
        }
    }

    public function download(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $filePath = $request->path;

        if (! Storage::exists($filePath)) {
            abort(404, 'File không tồn tại.');
        }

        $fullPath = Storage::path($filePath);
        $fileName = basename($filePath);

        return response()->download($fullPath, $fileName);
    }

    public function getUrl(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $filePath = $request->path;

        if (! Storage::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File không tồn tại.',
            ], 404);
        }

        $url = Storage::url($filePath);

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    public function move(Request $request): JsonResponse
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
            'destination' => 'required|string',
        ]);

        $moved = 0;
        $errors = [];

        foreach ($request->paths as $filePath) {
            try {
                if (Storage::exists($filePath)) {
                    $fileName = basename($filePath);
                    $newPath = $request->destination ? "{$request->destination}/{$fileName}" : $fileName;

                    if (Storage::exists($newPath)) {
                        $errors[] = "File {$fileName} đã tồn tại tại đích.";

                        continue;
                    }

                    Storage::move($filePath, $newPath);
                    $moved++;
                }
            } catch (\Exception $e) {
                $errors[] = "Không thể di chuyển {$filePath}: ".$e->getMessage();
            }
        }

        return response()->json([
            'success' => $moved > 0,
            'message' => "Đã di chuyển {$moved} item(s).",
            'errors' => $errors,
        ]);
    }

    public function copy(Request $request): JsonResponse
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
            'destination' => 'required|string',
        ]);

        $copied = 0;
        $errors = [];

        foreach ($request->paths as $filePath) {
            try {
                if (Storage::exists($filePath)) {
                    $fileName = basename($filePath);
                    $newPath = $request->destination ? "{$request->destination}/{$fileName}" : $fileName;

                    if (Storage::exists($newPath)) {
                        $name = pathinfo($fileName, PATHINFO_FILENAME);
                        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                        $newPath = $request->destination ? "{$request->destination}/{$name}_copy.{$extension}" : "{$name}_copy.{$extension}";
                    }

                    // Check if it's a directory
                    try {
                        $files = Storage::files($filePath);
                        $directories = Storage::directories($filePath);
                        if (count($files) > 0 || count($directories) > 0) {
                            $this->copyDirectory($filePath, $newPath);
                        } else {
                            Storage::copy($filePath, $newPath);
                        }
                    } catch (\Exception $e) {
                        Storage::copy($filePath, $newPath);
                    }
                    $copied++;
                }
            } catch (\Exception $e) {
                $errors[] = "Không thể copy {$filePath}: ".$e->getMessage();
            }
        }

        return response()->json([
            'success' => $copied > 0,
            'message' => "Đã copy {$copied} item(s).",
            'errors' => $errors,
        ]);
    }

    private function listFiles(?string $path = ''): array
    {
        $path = $path ?? '';
        $files = [];
        $directories = [];

        // List of ignored patterns (from .gitignore and common system files)
        $ignoredPatterns = [
            '.git',
            'node_modules',
            'vendor',
            '.env',
            '.env.*',
            '.DS_Store',
            '.phpunit.cache',
            'bootstrap/ssr',
            'public/build',
            'public/hot',
            'public/storage',
            'storage/*.key',
            'storage/pail',
            'resources/js/actions',
            'resources/js/routes',
            'resources/js/wayfinder',
            '.phpactor.json',
            '.phpunit.result.cache',
            'Homestead.json',
            'Homestead.yaml',
            'npm-debug.log',
            'yarn-error.log',
            'auth.json',
            '.fleet',
            '.idea',
            '.nova',
            '.vscode',
            '.zed',
            'caddy',
            'frankenphp',
            'frankenphp-worker.php',
        ];

        try {
            // Get directories
            $dirs = Storage::directories($path);
            foreach ($dirs as $dir) {
                $name = basename($dir);

                // Skip ignored files/folders
                if ($this->shouldIgnore($name, $dir, $ignoredPatterns)) {
                    continue;
                }

                $stat = @stat(Storage::path($dir));

                $directories[] = [
                    'name' => $name,
                    'path' => $dir,
                    'type' => 'directory',
                    'size' => null,
                    'modified' => $stat ? $stat['mtime'] : time(),
                    'extension' => null,
                ];
            }

            // Get files
            $fileList = Storage::files($path);
            foreach ($fileList as $file) {
                $name = basename($file);

                // Skip ignored files/folders
                if ($this->shouldIgnore($name, $file, $ignoredPatterns)) {
                    continue;
                }

                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $stat = @stat(Storage::path($file));

                $files[] = [
                    'name' => $name,
                    'path' => $file,
                    'type' => 'file',
                    'size' => $stat ? $stat['size'] : 0,
                    'modified' => $stat ? $stat['mtime'] : time(),
                    'extension' => $extension,
                ];
            }

            // Sort: directories first, then files
            usort($directories, fn ($a, $b) => strcmp($a['name'], $b['name']));
            usort($files, fn ($a, $b) => strcmp($a['name'], $b['name']));

            return array_merge($directories, $files);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if file/folder should be ignored
     */
    private function shouldIgnore(string $name, string $fullPath, array $ignoredPatterns): bool
    {
        // Skip hidden files/folders (starting with .)
        if (str_starts_with($name, '.')) {
            // Allow some common visible hidden files
            $allowedHidden = ['.htaccess', '.gitignore', '.gitkeep'];
            if (! in_array($name, $allowedHidden)) {
                return true;
            }
        }

        // Check against ignored patterns
        foreach ($ignoredPatterns as $pattern) {
            // Exact match
            if ($name === $pattern) {
                return true;
            }

            // Pattern with wildcard
            if (str_contains($pattern, '*')) {
                $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
                if (preg_match("/^{$regex}$/", $name) || preg_match("/{$regex}/", $fullPath)) {
                    return true;
                }
            }

            // Check if path contains the pattern
            if (str_contains($fullPath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function copyDirectory(string $source, string $destination): void
    {
        Storage::makeDirectory($destination);

        // Copy files
        $files = Storage::files($source);
        foreach ($files as $file) {
            $relativePath = str_replace($source.'/', '', $file);
            $destPath = "{$destination}/{$relativePath}";
            Storage::copy($file, $destPath);
        }

        // Copy subdirectories
        $directories = Storage::directories($source);
        foreach ($directories as $dir) {
            $relativePath = str_replace($source.'/', '', $dir);
            $destPath = "{$destination}/{$relativePath}";
            $this->copyDirectory($dir, $destPath);
        }
    }

    /**
     * Format file/folder name to slug
     * Example: "Nhật Nguyên" -> "nhat-nguyen"
     */
    private function formatFileName(string $name): string
    {
        // Remove extension if present (we'll handle it separately)
        $name = trim($name);

        // Convert to slug using Laravel's Str::slug
        $slug = Str::slug($name, '-', 'vi');

        // If slug is empty after conversion, use a fallback
        if (empty($slug)) {
            // Remove special characters and convert to lowercase
            $slug = preg_replace('/[^a-z0-9]+/i', '-', $name);
            $slug = strtolower(trim($slug, '-'));
        }

        // Ensure slug is not empty
        if (empty($slug)) {
            $slug = 'file-'.time();
        }

        return $slug;
    }
}
