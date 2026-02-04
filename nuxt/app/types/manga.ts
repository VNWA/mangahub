import type { Author } from './author'
import type { Category } from './category'
import type { Badge } from './badge'
import type { Chapter } from './chapter'


export interface Manga {
  id: number | string
  slug: string
  name: string
  avatar: string
  author?: Author
  description?: string | null
  status?: string | null
  views?: number | null
  rating?: number | null
  follows?: number | null
  total_ratings?: number | null
  totalRatings?: number | null
  comment_count?: number | null
  categories?: Category[] | string[] | null
  badge?: Badge | null
  chapters?: Chapter[] | null
  lastChapter?: string | null
  created_at?: string | null
  updated_at?: string | null
}
