<template>
  <div class="space-y-3">
    <!-- List View -->
    <div v-if="readingHistory.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
      <NuxtLink
        v-for="item in readingHistory"
        :key="item.id"
        :to="`/${item.story.slug}/${item.lastChapterId}`"
        class="bg-white dark:bg-slate-800 rounded-lg p-4 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors flex gap-4"
      >
        <!-- Thumbnail -->
        <img
          :src="item.story.coverImage"
          :alt="item.story.title"
          class="w-16 h-20 object-cover rounded flex-shrink-0"
        />

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <h3 class="font-semibold text-slate-900 dark:text-white line-clamp-1">
            {{ item.story.title }}
          </h3>
          <p class="text-xs text-slate-600 dark:text-slate-400 mb-2">
            {{ item.story.author }}
          </p>
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <UIcon name="i-heroicons-document-text" class="w-4 h-4 text-slate-400" />
              <span class="text-xs text-slate-600 dark:text-slate-400">
                {{ item.lastChapterTitle }}
              </span>
            </div>
            <span class="text-xs text-slate-500">{{ formatDate(item.lastRead) }}</span>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="flex flex-col items-end justify-center">
          <div class="w-12 h-1.5 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
            <div
              class="h-full bg-primary transition-all"
              :style="{ width: `${item.progress}%` }"
            ></div>
          </div>
          <span class="text-xs text-slate-600 dark:text-slate-400 mt-1">{{ item.progress }}%</span>
        </div>
      </NuxtLink>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <UIcon name="i-heroicons-clock" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
      <p class="text-slate-600 dark:text-slate-400">Chưa có lịch sử đọc</p>
    </div>
  </div>
</template>

<script setup lang="ts">
interface ReadingHistoryItem {
  id: string
  story: {
    id: string
    title: string
    slug: string
    author: string
    coverImage: string
  }
  lastChapterId: string
  lastChapterTitle: string
  lastRead: string
  progress: number
}

interface Props {
  readingHistory: ReadingHistoryItem[]
}

defineProps<Props>()

const formatDate = (date: string) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor(diff / (1000 * 60 * 60))

  if (hours < 1) return 'Vừa xong'
  if (hours < 24) return `${hours}h trước`
  if (days < 7) return `${days}d trước`
  return d.toLocaleDateString('vi-VN')
}
</script>
