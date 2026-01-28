<template>
  <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
      <h2 class="text-xl font-bold text-slate-900 dark:text-white">Danh sách chương ({{ chapters.length }})</h2>
    </div>

    <!-- Chapter List -->
    <div class="divide-y divide-slate-200 dark:divide-slate-700 max-h-96 overflow-y-auto">
      <NuxtLink
        v-for="(chapter, index) in chapters"
        :key="chapter.id || chapter.slug"
        :to="`/${storySlug}/${chapter.slug || chapter.id}`"
        class="px-6 py-4 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors flex items-center justify-between group"
      >
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-slate-900 dark:text-white group-hover:text-primary transition-colors line-clamp-1">
            {{ chapter.title }}
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ formatDate(chapter.uploadedAt) }}<span v-if="chapter.views"> • {{ formatNumber(chapter.views) }} lượt xem</span>
          </p>
        </div>
        <UIcon name="i-heroicons-arrow-right" class="w-5 h-5 text-slate-400 group-hover:text-primary ml-4 shrink-0" />
      </NuxtLink>
    </div>

    <!-- Pagination -->
    <div v-if="chapters.length > 20" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-center">
      <UButton
        color="neutral"
        variant="ghost"
        label="Tải thêm"
        @click="loadMore"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Chapter {
  id: string | number
  slug?: string
  number: number
  title: string
  uploadedAt: string
  views?: number
}

interface Props {
  chapters: Chapter[]
  storySlug: string
}

defineProps<Props>()

const emit = defineEmits<{
  'load-more': []
}>()

const formatDate = (date: string) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const minutes = Math.floor(diff / (1000 * 60))

  if (minutes < 1) return 'Vừa xong'
  if (hours < 1) return `${minutes}m trước`
  if (days < 1) return `${hours}h trước`
  if (days < 7) return `${days}d trước`
  return d.toLocaleDateString('vi-VN')
}

const formatNumber = (num: number) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

const loadMore = () => {
  emit('load-more')
}
</script>
