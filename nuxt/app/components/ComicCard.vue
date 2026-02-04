<template>
  <article
    class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 group h-full flex flex-col">
    <!-- Comic Thumbnail - Clickable -->
    <NuxtLink :to="`/${comic.slug || comic.id}`"
      class="relative overflow-hidden bg-gray-200 dark:bg-zinc-700 h-64 block">
      <Image v-if="comic.avatar" :src="comic.avatar" :alt="`${comic.title} thumbnail`"
        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
      <div v-else class="w-full h-full flex items-center justify-center">
        <UIcon name="i-heroicons-photo" class="w-12 h-12 text-gray-400" />
      </div>

      <!-- Overlay on hover -->
      <div
        class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
      </div>

      <div class="absolute top-2 right-2 z-10">
        <UBadge :label="comic.status || ''" :color="comic.status === 'Đang cập nhật' ? 'success' : 'neutral'"
          variant="subtle" />
      </div>

      <div
        class="absolute bottom-2 left-2 z-10 bg-black/70 backdrop-blur-sm text-white px-2 py-1 rounded-md flex items-center gap-1">
        <UIcon name="i-heroicons-star-solid" class="w-4 h-4 text-yellow-400" />
        <span class="text-sm font-semibold">{{ comic.rating }}</span>
      </div>
    </NuxtLink>

    <div class="p-4 flex-1 flex flex-col">
      <NuxtLink :to="`/${comic.slug || comic.id}`" class="flex-1">
        <h3
          class="text-lg font-bold text-zinc-900 dark:text-white line-clamp-2 mb-2 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
          {{ comic.title }}
        </h3>

        <p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2 mb-3">
          {{ comic.author }}
        </p>

        <div class="flex flex-wrap gap-2 mb-3">
          <UBadge v-for="category in comicCategories.slice(0, 2)" :key="category" :label="category" variant="soft"
            size="xs" />
        </div>
      </NuxtLink>

      <!-- Last Chapter - Clickable -->
      <NuxtLink v-if="comic.lastChapter" :to="`/${comic.slug || comic.id}/${getLastChapterNumber(comic.lastChapter)}`"
        class="text-xs text-zinc-500 dark:text-zinc-400 mb-3 hover:text-purple-600 dark:hover:text-purple-400 transition-colors flex items-center gap-1 group/chapter">
        <UIcon name="i-heroicons-document-text"
          class="w-3 h-3 group-hover/chapter:tranzinc-x-0.5 transition-transform" />
        <span>{{ comic.lastChapter }}</span>
      </NuxtLink>

      <NuxtLink :to="`/${comic.slug || comic.id}`">
        <UButton color="primary" variant="soft" block icon="i-heroicons-arrow-right" label="Đọc ngay" class="mt-auto" />
      </NuxtLink>
    </div>
  </article>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'

const props = defineProps<{
  comic: Manga
}>()

// Normalize categories to string array
const comicCategories = computed(() => {
  if (!props.comic.categories) return []
  if (typeof props.comic.categories[0] === 'string') {
    return props.comic.categories as string[]
  }
  return (props.comic.categories as Array<{ name: string }>).map(c => c.name)
})

const getLastChapterNumber = (lastChapter: string) => {
  const match = lastChapter.match(/\d+/)
  return match ? match[0] : '1'
}
</script>
