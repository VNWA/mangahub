import type { HttpUseFetchOptions } from '~/index';

export function useHttp<T = unknown>(
  url: string | (() => string),
  options?: HttpUseFetchOptions<T>,
) {

  return useFetch<T>(url, {
    ...options,
    $fetch: $http,
  } as any);
}