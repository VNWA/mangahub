import type { NitroFetchRequest } from 'nitropack/types';
import type { HttpFetchOptions } from '~';
import { joinURL } from 'ufo'

export function $http<T = unknown>(request: NitroFetchRequest, opts?: HttpFetchOptions): Promise<T> {
  const { $http: http } = useNuxtApp();

  return http(request, opts);
}

export function $storage(path: string): string {
  if (!path) return '/no-image.png';

  const config = useRuntimeConfig();

  return path.startsWith('http://') || path.startsWith('https://')
    ? path
    : joinURL(config.public.storageUrl, path);
}

export function $formatTime(time: string) {
  const diff = Date.now() - new Date(time).getTime()
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Vừa xong'
  if (minutes < 60) return `${minutes} phút trước`
  if (hours < 24) return `${hours} giờ trước`
  if (days < 7) return `${days} ngày trước`
  return new Date(time).toLocaleDateString('vi-VN')
}
