<template>
  <div class="space-y-6">
    <!-- Section Header -->
    <div v-if="title" class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ title }}</h2>
        <p v-if="subtitle" class="text-zinc-600 dark:text-zinc-400 text-sm mt-1">{{ subtitle }}</p>
      </div>
      <NuxtLink v-if="viewAllLink" :to="viewAllLink" class="text-primary hover:text-primary/80 font-semibold text-sm">
        Xem tất cả →
      </NuxtLink>
    </div>

    <!-- Grid -->
    <div :class="[
      'grid gap-4 md:gap-6',
      {
        'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5': columns === 5,
        'grid-cols-1 sm:grid-cols-2 md:grid-cols-3': columns === 3,
        'grid-cols-1': columns === 1
      }
    ]">
      <StoryCard v-for="story in stories" :key="story.id" :story="story" variant="grid" />
    </div>

    <!-- Loading Skeleton -->
    <div v-if="isLoading" class="grid gap-4 md:gap-6 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
      <div v-for="i in 10" :key="i" class="bg-zinc-200 dark:bg-zinc-700 rounded-lg h-96 animate-pulse"></div>
    </div>

    <!-- Empty State -->
    <div v-if="!isLoading && stories.length === 0" class="text-center py-12">
      <UIcon name="i-heroicons-inbox" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
      <p class="text-zinc-600 dark:text-zinc-400">Không tìm thấy truyện nào</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'

interface Props {
  stories: Manga[]
  title?: string
  subtitle?: string
  viewAllLink?: string
  columns?: 1 | 3 | 5
  isLoading?: boolean
}

withDefaults(defineProps<Props>(), {
  columns: 5,
  isLoading: false
})
</script>
