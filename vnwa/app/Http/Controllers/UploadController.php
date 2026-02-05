<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use App\Models\TemporaryUpload;
use App\Helpers\Image as ImageHelper;
class UploadController extends Controller
{
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,avif,svg,ico,webp',
                'max:2048',
            ],
            'current_url' => 'nullable|string',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
            'format' => 'nullable|in:webp,jpeg,png',
            'quality' => 'nullable|integer|min:1|max:100',
        ]);

        $image = $request->file('image');
        $currentPath = $request->input('current_url');

        if ($currentPath && Storage::exists($currentPath)) {
            Storage::delete($currentPath);
            TemporaryUpload::where('path', $currentPath)->delete();
        }

        $path = $this->storeResizedImage(
            file: $image,
            width: $request->input('width'),
            height: $request->input('height'),
            format: $request->input('format'),   // ❗ chỉ convert khi có
            quality: $request->input('quality', 90),
        );

        return response()->json([
            'success' => true,
            'url' => $path,
        ]);
    }

    private function storeResizedImage(
        UploadedFile $file,
        ?int $width = null,
        ?int $height = null,
        ?string $format = null,
        int $quality = 90
    ): string {
        $directory = 'images';
        $datePath = now()->format('Y-m-d');

        $originalExtension = $file->getClientOriginalExtension();
        $extension = $format ?: $originalExtension;

        $fileName = Str::random(40) . '.' . $extension;
        $relativePath = "{$directory}/{$datePath}/{$fileName}";

        $shouldResize = is_numeric($width) || is_numeric($height);
        $shouldConvert = !is_null($format);


        if (!$shouldResize && !$shouldConvert) {
            return $file->storeAs("{$directory}/{$datePath}", $fileName);
        }

        // cần xử lý ảnh
        $tempFilePath = sys_get_temp_dir() . '/' . Str::random(40) . '.' . $extension;

        try {
            ImageHelper::convert(
                source: $file->getRealPath(),
                target: $tempFilePath,
                width: $shouldResize ? $width : null,
                height: $shouldResize ? $height : null,
                extension: $extension,
                quality: $quality,
            );

            Storage::put($relativePath, file_get_contents($tempFilePath));

            TemporaryUpload::create([
                'path' => $relativePath,
            ]);

            return $relativePath;
        } catch (\Throwable $e) {
            \Log::error('Image processing failed', [
                'error' => $e->getMessage(),
            ]);

            return '';
        } finally {
            if (file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }
    }

    public function tempDelete(Request $request): JsonResponse
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
        ]);
        TemporaryUpload::whereIn('path', $request->paths)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully.',
            'url' => $request->path,
        ]);
    }
}
