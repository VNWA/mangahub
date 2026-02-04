import type { Manga, Chapter, Author, Category, Badge } from './index'

/**
 * API Response types
 */
export interface ApiResponse<T> {
  ok: boolean
  data: T
  message?: string
}

/**
 * Manga API Response (from /mangas/:slug endpoint)
 */
export interface MangaApiResponse {
  id: number
  name: string
  slug: string
  avatar: string | null
  description: string
  status: string
  views: number
  rating: number
  total_ratings: number
  follows: number
  author: Author | null
  badge: Badge | null
  categories: Category[]
  comment_count: number
  chapters: Array<{
    id: number
    name: string
    slug: string
    order: number
    created_at: string
  }>
}
