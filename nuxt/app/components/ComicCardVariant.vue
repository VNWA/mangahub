<template>
  <!-- List View Card -->
  <article v-if="variant === 'list'"
    class="bg-white dark:bg-zinc-800 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-all p-4 flex gap-4">
    <div class="flex-shrink-0 w-24 h-32">
      <Image :src="comic.avatar" :alt="`${comic.title} thumbnail`" container-class="w-24 h-32" hoverable />
    </div>
    <div class="flex-1 flex flex-col justify-between">
      <div>
        <div class="flex items-start justify-between mb-2">
          <h3 class="text-lg font-bold text-zinc-900 dark:text-white line-clamp-2 flex-1">
            {{ comic.title }}
          </h3>
          <UBadge :label="comic.status" :color="comic.status === 'Đang cập nhật' ? 'success' : 'neutral'"
            variant="subtle" size="sm" />
        </div>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">{{ comic.author }}</p>
        <p class="text-sm text-zinc-700 dark:text-zinc-300 line-clamp-2">{{ comic.description }}</p>
      </div>
      <div class="flex items-center justify-between pt-2">
        <div class="flex gap-3">
          <div class="flex items-center gap-1">
            <UIcon name="i-heroicons-star-solid" class="w-4 h-4 text-yellow-500" />
            <span class="text-sm font-semibold">{{ comic.rating }}</span>
          </div>
          <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ comic.views }} lượt xem</div>
        </div>
        <UButton to="`/comic/${comic.id}`" color="primary" variant="soft" size="sm" label="Đọc" />
      </div>
    </div>
  </article>

  <!-- Grid View Card -->
  <article v-else-if="variant === 'grid'"
    class="bg-white dark:bg-zinc-800 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
    <div class="relative overflow-hidden h-64">
      <Image :src="comic.avatar" :alt="`${comic.title} thumbnail`" hoverable />
      <div class="absolute top-2 right-2">
        <UBadge :label="comic.status" :color="comic.status === 'Đang cập nhật' ? 'success' : 'neutral'"
          variant="subtle" />
      </div>
      <div class="absolute bottom-2 left-2 bg-black/70 text-white px-2 py-1 rounded flex items-center gap-1">
        <UIcon name="i-heroicons-star" class="w-4 h-4" />
        <span class="text-sm font-semibold">{{ comic.rating }}</span>
      </div>
    </div>
    <div class="p-4">
      <h3 class="text-lg font-bold text-zinc-900 dark:text-white line-clamp-2 mb-2">
        {{ comic.title }}
      </h3>
      <p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2 mb-3">{{ comic.author }}</p>
      <div class="flex flex-wrap gap-2 mb-3">
        <UBadge v-for="category in comic.categories.slice(0, 2)" :key="category" :label="category" variant="soft"
          size="xs" />
      </div>
      <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">{{ comic.lastChapter }}</p>
      <UButton to="`/comic/${comic.id}`" color="primary" variant="soft" block label="Đọc ngay" size="sm" />
    </div>
  </article>

  <!-- Featured Card -->
  <article v-else-if="variant === 'featured'"
    class="relative bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg overflow-hidden text-white shadow-lg h-80">
    <div class="absolute inset-0 opacity-30">
      <Image :src="comic.avatar" :alt="`${comic.title} background`" container-class="w-full h-full" />
    </div>
    <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent"></div>
    <div class="relative h-full flex items-end p-6">
      <div>
        <UBadge :label="comic.status" :color="comic.status === 'Đang cập nhật' ? 'success' : 'neutral'" class="mb-3" />
        <h2 class="text-4xl font-bold mb-2">{{ comic.title }}</h2>
        <p class="text-lg text-blue-100 mb-4">{{ comic.author }}</p>
        <p class="text-sm text-blue-50 line-clamp-2 mb-4 max-w-md">{{ comic.description }}</p>
        <div class="flex gap-3">
          <UButton to="`/comic/${comic.id}`" color="primary" label="Đọc ngay" icon="i-heroicons-arrow-right" />
          <UButton color="primary" variant="outline" icon="i-heroicons-heart" label="Yêu thích" />
        </div>
      </div>
    </div>
  </article>

  <!-- Compact Card -->
  <article v-else-if="variant === 'compact'"
    class="bg-white dark:bg-zinc-800 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all group">
    <div class="relative h-40">
      <Image :src="comic.avatar" :alt="`${comic.title} thumbnail`" hoverable />
    </div>
    <div class="p-3">
      <h4 class="font-semibold text-zinc-900 dark:text-white line-clamp-1 text-sm mb-1">{{ comic.title }}</h4>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-1">
          <UIcon name="i-heroicons-star-solid" class="w-3 h-3 text-yellow-500" />
          <span class="text-xs font-semibold">{{ comic.rating }}</span>
        </div>
        <UButton icon="i-heroicons-arrow-right" color="primary" variant="ghost" size="xs" square />
      </div>
    </div>
  </article>

  <!-- Carousel Card -->
  <article v-else-if="variant === 'carousel'"
    class="relative rounded-lg overflow-hidden group h-96 bg-zinc-200 dark:bg-zinc-700">
    <Image :src="comic.thumbnail" :alt="`${comic.title} thumbnail`" container-class="w-full h-full" />
    <div
      class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6 text-white">
      <h3 class="text-2xl font-bold mb-2">{{ comic.title }}</h3>
      <p class="text-sm text-gray-300 mb-4">{{ comic.author }}</p>
      <UButton to="`/comic/${comic.id}`" color="primary" size="sm" label="Xem chi tiết" />
    </div>
  </article>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'

type Variant = 'grid' | 'list' | 'featured' | 'compact' | 'carousel'

interface Props {
  comic: Manga
  variant?: Variant
}

withDefaults(defineProps<Props>(), {
  variant: 'grid'
})
</script>
