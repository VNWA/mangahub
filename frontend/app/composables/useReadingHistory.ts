import { useAuthStore } from '~/stores/auth'

interface ReadingHistoryItem {
  manga_id: number
  chapter_id?: number
  chapter_order?: number
  chapter_name?: string
  last_read_at?: string
}

export const useReadingHistory = () => {
  const auth = useAuthStore()
  const config = useRuntimeConfig()

  const historyCookie = useCookie<ReadingHistoryItem[]>('reading_history', {
    default: () => [],
    maxAge: 60 * 60 * 24 * 365, // 1 year
  })

  // Get reading history from API or cookie
  const getHistory = async (): Promise<ReadingHistoryItem[]> => {
    if (auth.logged && auth.user?.email) {
      try {
        const data = await $http<{ ok: boolean; data: any[] }>('/reading-history')
        if (data?.ok && data.data) {
          return data.data.map((item: any) => ({
            manga_id: item.manga.id,
            chapter_id: item.chapter?.id,
            chapter_order: item.chapter_order || item.chapter?.order,
            chapter_name: item.chapter_name || item.chapter?.name,
            last_read_at: item.last_read_at,
          }))
        }
      } catch (error) {
        console.error('Failed to fetch reading history:', error)
      }
    }
    return historyCookie.value || []
  }

  // Add/Update reading history
  const addHistory = async (item: ReadingHistoryItem): Promise<boolean> => {
    if (auth.logged && auth.user?.email) {
      try {
        const data = await $http<{ ok: boolean }>('/reading-history', {
          method: 'POST',
          body: item
        })
        if (data?.ok) {
          return true
        }
      } catch (error) {
        console.error('Failed to add reading history:', error)
        return false
      }
    } else {
      // Save to cookie
      const existingIndex = historyCookie.value.findIndex(
        h => h.manga_id === item.manga_id
      )
      
      if (existingIndex >= 0) {
        historyCookie.value[existingIndex] = {
          ...historyCookie.value[existingIndex],
          ...item,
          last_read_at: new Date().toISOString(),
        }
      } else {
        historyCookie.value = [
          ...historyCookie.value,
          {
            ...item,
            last_read_at: new Date().toISOString(),
          }
        ]
      }
      return true
    }
    return false
  }

  // Get history for a specific manga
  const getMangaHistory = async (mangaId: number): Promise<ReadingHistoryItem | null> => {
    const history = await getHistory()
    return history.find(h => h.manga_id === mangaId) || null
  }

  // Clear reading history
  const clearHistory = async (): Promise<boolean> => {
    if (auth.logged && auth.user?.email) {
      try {
        const data = await $http<{ ok: boolean }>('/reading-history', {
          method: 'DELETE'
        })
        if (data?.ok) {
          return true
        }
      } catch (error) {
        console.error('Failed to clear reading history:', error)
        return false
      }
    } else {
      historyCookie.value = []
      return true
    }
    return false
  }

  // Sync cookie history to database
  const syncHistory = async (): Promise<void> => {
    if (auth.logged && auth.user?.email && historyCookie.value.length > 0) {
      try {
        await $http('/sync-data', {
          method: 'POST',
          body: {
            reading_history: historyCookie.value
          }
        })
        // Clear cookie after sync
        historyCookie.value = []
      } catch (error) {
        console.error('Failed to sync reading history:', error)
      }
    }
  }

  return {
    historyCookie,
    getHistory,
    addHistory,
    getMangaHistory,
    clearHistory,
    syncHistory,
  }
}
