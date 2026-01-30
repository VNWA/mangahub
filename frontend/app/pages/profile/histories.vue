<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Lịch sử đọc</h2>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 5" :key="i" class="animate-pulse">
        <div class="h-24 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
      </div>
    </div>

    <div v-else-if="readingHistory.length === 0" class="text-center py-12">
      <UIcon name="i-heroicons-clock" class="w-12 h-12 text-slate-400 mx-auto mb-3" />
      <p class="text-slate-600 dark:text-slate-400">Chưa có lịch sử đọc</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="item in readingHistory"
        :key="item.id"
        class="bg-white dark:bg-slate-800 rounded-lg p-4 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
      >
        <NuxtLink
          :to="item.chapter ? `/${item.manga.slug}/${item.chapter.slug}` : `/${item.manga.slug}`"
          class="flex gap-4"
        >
          <!-- Thumbnail -->
          <img
            :src="item.manga.avatar || item.manga.coverImage || 'https://via.placeholder.com/80x100'"
            :alt="item.manga.name"
            class="w-16 h-20 object-cover rounded flex-shrink-0"
          />

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-slate-900 dark:text-white line-clamp-1 mb-1">
              {{ item.manga.name }}
            </h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 mb-2">
              {{ item.manga.author?.name || 'Chưa có tác giả' }}
            </p>
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <UIcon name="i-heroicons-document-text" class="w-4 h-4 text-slate-400" />
                <span class="text-xs text-slate-600 dark:text-slate-400">
                  {{ item.chapter?.name || item.chapter_name || 'Chưa đọc' }}
                </span>
              </div>
              <span class="text-xs text-slate-500">{{ formatDate(item.last_read_at) }}</span>
            </div>
          </div>
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const loading = ref(true)
const readingHistory = ref<any[]>([])

const formatDate = (date: string) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Vừa xong'
  if (minutes < 60) return `${minutes} phút trước`
  if (hours < 24) return `${hours} giờ trước`
  if (days < 7) return `${days} ngày trước`
  return d.toLocaleDateString('vi-VN', { year: 'numeric', month: 'long', day: 'numeric' })
}

const loadHistory = async () => {
  loading.value = true
  try {
    const data = await $http<{
      ok: boolean
      data: Array<{
        id: number
        manga: {
          id: number
          name: string
          slug: string
          avatar?: string
          coverImage?: string
          author?: {
            name: string
          }
        }
        chapter: {
          id: number
          name: string
          slug: string
          order: number
        } | null
        chapter_name: string | null
        chapter_order: number | null
        last_read_at: string
        created_at: string
      }>
    }>('/reading-history')

    if (data?.ok && data.data) {
      readingHistory.value = data.data
    }
  } catch (error) {
    console.error('Failed to load reading history:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadHistory()
})
</script>
