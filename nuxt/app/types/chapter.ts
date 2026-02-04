/**
 * Chapter interface
 */
export interface Chapter {
  id: number
  name: string
  slug: string
  order: number
  created_at?: string
  views?: number
  uploadedAt?: string
  number?: number
  title?: string
  url?: string
}
