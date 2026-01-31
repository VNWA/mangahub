import type { HttpUseFetchOptions } from '~/index';

export function useLazyHttp<T = unknown>(
  url: string | (() => string),
  options?: HttpUseFetchOptions<T>,
) {

  return useLazyFetch<T>(url, {
    ...options,
    $fetch: $http,
  } as any);
}