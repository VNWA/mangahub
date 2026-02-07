/**
 * Storage utility functions for handling file URLs
 */

/**
 * Get the storage URL for a given path
 * If the path is already a full URL, return it as is
 * If it's a storage path, prepend the storage URL
 */
export function getStorageUrl(path: string | null | undefined): string {
    if (!path) {
        return '/vnwa/no-image.jpg';
    }

    // If already a full URL (http:// or https://), return as is
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    const storageUrl = import.meta.env.VITE_APP_STORAGE_URL || window.location.origin;
    const storagePath = path.startsWith('/') ? path : `/${path}`;

    return `${storageUrl}${storagePath}`;
}

/**
 * Check if a URL is a storage URL (local storage, not external)
 */
export function isStorageUrl(url: string): boolean {
    if (!url) {
        return false;
    }

    // Check if it's a local storage URL
    const storageUrl = import.meta.env.VITE_APP_URL || window.location.origin;
    return url.startsWith(`${storageUrl}/storage/`) || url.startsWith('/storage/');
}

/**
 * Extract the storage path from a full storage URL
 * Returns null if it's not a storage URL
 */
export function getStoragePath(url: string | null | undefined): string | null {
    if (!url) {
        return null;
    }

    if (!isStorageUrl(url)) {
        return null;
    }

    const storageUrl = import.meta.env.VITE_APP_URL || window.location.origin;

    // Remove the storage URL prefix
    if (url.startsWith(`${storageUrl}/storage/`)) {
        return url.replace(`${storageUrl}/storage/`, '');
    }

    if (url.startsWith('/storage/')) {
        return url.replace('/storage/', '');
    }

    return null;
}
