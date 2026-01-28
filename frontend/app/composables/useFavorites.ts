import { useAuthStore } from '~/stores/auth'

export const useFavorites = () => {
  const auth = useAuthStore()
  const config = useRuntimeConfig()

  const favoritesCookie = useCookie<number[]>('favorites', {
    default: () => [],
    maxAge: 60 * 60 * 24 * 365, // 1 year
  })

  // Get favorites from API or cookie
  const getFavorites = async (): Promise<number[]> => {
    if (auth.logged && auth.user?.email) {
      try {
        const data = await $http<{ ok: boolean; data: any[] }>('/favorites')
        if (data?.ok && data.data) {
          return data.data.map((item: any) => item.id)
        }
      } catch (error) {
        console.error('Failed to fetch favorites:', error)
      }
    }
    return favoritesCookie.value || []
  }

  // Check if manga is favorited
  const isFavorited = async (mangaId: number): Promise<boolean> => {
    if (auth.logged && auth.user?.email) {
      try {
        const data = await $http<{ ok: boolean; is_favorited: boolean }>(`/favorites/check/${mangaId}`)
        return data?.is_favorited || false
      } catch (error) {
        console.error('Failed to check favorite:', error)
      }
    }
    return favoritesCookie.value.includes(mangaId)
  }

  // Add to favorites
  const addFavorite = async (mangaId: number): Promise<boolean> => {
    if (auth.logged && auth.user?.email) {
      try {
        const data = await $http<{ ok: boolean }>('/favorites', {
          method: 'POST',
          body: { manga_id: mangaId }
        })
        if (data?.ok) {
          return true
        }
      } catch (error) {
        console.error('Failed to add favorite:', error)
        return false
      }
    } else {
      // Save to cookie
      if (!favoritesCookie.value.includes(mangaId)) {
        favoritesCookie.value = [...favoritesCookie.value, mangaId]
      }
      return true
    }
    return false
  }

  // Remove from favorites
  const removeFavorite = async (mangaId: number): Promise<boolean> => {
    if (auth.logged && auth.user?.email) {
      try {
        const data = await $http<{ ok: boolean }>(`/favorites/${mangaId}`, {
          method: 'DELETE'
        })
        if (data?.ok) {
          return true
        }
      } catch (error) {
        console.error('Failed to remove favorite:', error)
        return false
      }
    } else {
      // Remove from cookie
      favoritesCookie.value = favoritesCookie.value.filter(id => id !== mangaId)
      return true
    }
    return false
  }

  // Sync cookie favorites to database
  const syncFavorites = async (): Promise<void> => {
    if (auth.logged && auth.user?.email && favoritesCookie.value.length > 0) {
      try {
        await $http('/sync-data', {
          method: 'POST',
          body: {
            favorites: favoritesCookie.value
          }
        })
        // Clear cookie after sync
        favoritesCookie.value = []
      } catch (error) {
        console.error('Failed to sync favorites:', error)
      }
    }
  }

  return {
    favoritesCookie,
    getFavorites,
    isFavorited,
    addFavorite,
    removeFavorite,
    syncFavorites,
  }
}
