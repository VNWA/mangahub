<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DeleteImageStorageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $urls = [],
        public array $directories = [],
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Xóa các file ảnh
        foreach ($this->urls as $url) {
            if (is_string($url) && ! empty($url)) {
                $this->deleteImageIfLocal($url);
            }
        }

        // Xóa các thư mục
        foreach ($this->directories as $directory) {
            if (is_string($directory) && ! empty($directory)) {
                if (Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->deleteDirectory($directory);
                }
            }
        }
    }

    /**
     * Delete image if it's a local storage file
     */
    private function deleteImageIfLocal(string $url): void
    {
        // Bỏ qua nếu là external URL (không phải của storage)
        if ($this->isExternalUrl($url)) {
            return;
        }

        $storagePath = $this->extractStoragePath($url);

        if ($storagePath && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }

    /**
     * Check if URL is external (not from our storage)
     */
    private function isExternalUrl(string $url): bool
    {
        // Nếu là full URL với domain khác
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $appUrl = config('app.url');
            $storageUrl = config('filesystems.disks.public.url');

            // Kiểm tra xem URL có chứa domain của chúng ta không
            $parsedUrl = parse_url($url);
            $parsedAppUrl = parse_url($appUrl);
            $parsedStorageUrl = parse_url($storageUrl);

            // Nếu hostname khác với app URL hoặc storage URL thì là external
            if (isset($parsedUrl['host'])) {
                $urlHost = $parsedUrl['host'];
                $appHost = $parsedAppUrl['host'] ?? null;
                $storageHost = $parsedStorageUrl['host'] ?? null;

                // Nếu hostname không khớp với app hoặc storage URL thì là external
                if ($appHost && $urlHost !== $appHost) {
                    return true;
                }
                if ($storageHost && $urlHost !== $storageHost) {
                    return true;
                }
            }

            // Nếu không có /storage/ trong URL thì có thể là external
            if (! str_contains($url, '/storage/')) {
                return true;
            }
        }

        return false;
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
