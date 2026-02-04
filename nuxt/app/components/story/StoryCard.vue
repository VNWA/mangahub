<template>
  <div v-if="variant === 'grid'"
    class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 group h-full flex flex-col">
    <!-- Image - Clickable to manga -->
    <NuxtLink :to="`/${story.slug}`" class="relative overflow-hidden bg-zinc-200 dark:bg-zinc-700 h-64 block">
      <Image v-if="storyCoverImage" :src="storyCoverImage" :alt="storyTitle"
        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
      <div v-else class="w-full h-full flex items-center justify-center">
        <UIcon name="i-heroicons-photo" class="w-12 h-12 text-gray-400" />
      </div>

      <!-- Overlay on hover -->
      <div
        class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
      </div>

      <!-- Badge -->
      <div class="absolute top-2 right-2 z-10">
        <UBadge :label="storyStatus" :color="storyStatus === 'Đang ra' ? 'success' : 'secondary'" size="sm" />
      </div>

      <!-- Rating -->
      <div
        class="absolute bottom-2 left-2 z-10 bg-black/70 backdrop-blur-sm text-white px-2 py-1 rounded-md flex items-center gap-1">
        <UIcon name="i-heroicons-star-solid" class="w-4 h-4 text-yellow-400" />
        <span class="text-sm font-semibold">{{ storyRating.toFixed(1) }}</span>
      </div>
    </NuxtLink>

    <!-- Content -->
    <div class="p-4 flex-1 flex flex-col">
      <NuxtLink :to="`/${story.slug}`" class="flex-1">
        <h3
          class="font-bold text-zinc-900 dark:text-white line-clamp-2 mb-1 text-sm hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
          {{ storyTitle }}
        </h3>
        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-3">{{ storyAuthor }}</p>

        <!-- Categories -->
        <div v-if="storyCategories.length > 0" class="flex flex-wrap gap-1 mb-3">
          <UBadge v-for="cat in storyCategories.slice(0, 2)" :key="cat" :label="cat" variant="soft" size="xs" />
        </div>
      </NuxtLink>

      <!-- Last Chapter - Clickable -->
      <NuxtLink v-if="storyLastChapter && storyLastChapter !== 'Chưa có chương'"
        :to="`/${story.slug}/${getLastChapterNumber(storyLastChapter)}`"
        class="text-xs text-zinc-500 dark:text-zinc-400 mb-2 hover:text-purple-600 dark:hover:text-purple-400 transition-colors flex items-center gap-1 group/chapter">
        <UIcon name="i-heroicons-document-text"
          class="w-3 h-3 group-hover/chapter:tranzinc-x-0.5 transition-transform" />
        <span>{{ storyLastChapter }}</span>
      </NuxtLink>
      <div v-else class="text-xs text-zinc-400 dark:text-zinc-500 mb-2">
        Chưa có chương
      </div>

      <!-- Views -->
      <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 mb-3">
        <UIcon name="i-heroicons-eye" class="w-3 h-3" />
        <span>{{ formatNumber(storyViews) }} lượt xem</span>
      </div>

      <!-- Action Button -->
      <NuxtLink :to="`/${story.slug}`">
        <UButton color="primary" variant="soft" block size="sm" label="Đọc ngay" class="mt-auto" />
      </NuxtLink>
    </div>
  </div>

  <!-- List View -->
  <div v-else-if="variant === 'list'"
    class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all p-4 flex gap-4">
    <!-- Thumbnail - Clickable -->
    <NuxtLink :to="`/${story.slug}`"
      class="flex-shrink-0 w-24 h-32 rounded-lg overflow-hidden bg-zinc-200 dark:bg-zinc-700 group/thumb">
      <Image v-if="storyCoverImage" :src="storyCoverImage" :alt="storyTitle"
        class="w-full h-full object-cover group-hover/thumb:scale-110 transition-transform duration-300" />
      <div v-else class="w-full h-full flex items-center justify-center">
        <UIcon name="i-heroicons-photo" class="w-8 h-8 text-gray-400" />
      </div>
    </NuxtLink>

    <!-- Content -->
    <div class="flex-1 flex flex-col justify-between">
      <div>
        <div class="flex items-start justify-between gap-2 mb-2">
          <NuxtLink :to="`/${story.slug}`" class="flex-1">
            <h3 class="font-bold text-zinc-900 dark:text-white hover:text-primary line-clamp-2">
              {{ storyTitle }}
            </h3>
          </NuxtLink>
          <UBadge :label="storyStatus" :color="storyStatus === 'Đang ra' ? 'success' : 'secondary'" size="sm" />
        </div>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">{{ storyAuthor }}</p>
        <p v-if="story.description" class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ story.description
          }}</p>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
          <div class="flex items-center gap-1">
            <UIcon name="i-heroicons-star-solid" class="w-3 h-3 text-yellow-400" />
            <span>{{ storyRating.toFixed(1) }}</span>
          </div>
          <div class="flex items-center gap-1">
            <UIcon name="i-heroicons-eye" class="w-3 h-3" />
            <span>{{ formatNumber(storyViews) }}</span>
          </div>
        </div>
        <NuxtLink v-if="storyLastChapter && storyLastChapter !== 'Chưa có chương'"
          :to="`/${story.slug}/${getLastChapterNumber(storyLastChapter)}`"
          class="text-xs font-semibold hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
          {{ storyLastChapter }}
        </NuxtLink>
        <span v-else class="text-xs text-zinc-400">Chưa có chương</span>
      </div>
    </div>
  </div>

  <!-- Ranked View -->
  <div v-else-if="variant === 'ranked'"
    class="bg-white dark:bg-zinc-800 rounded-lg p-4 flex gap-4 hover:shadow-md transition-all">
    <!-- Rank -->
    <div
      class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 text-white font-bold text-xl flex-shrink-0">
      {{ rank }}
    </div>

    <!-- Content -->
    <div class="flex-1 flex items-center gap-4 min-w-0">
      <Image v-if="storyCoverImage" :src="storyCoverImage" :alt="storyTitle"
        class="w-16 h-20 rounded object-cover flex-shrink-0" />
      <div class="flex-1 min-w-0">
        <h3 class="font-bold text-zinc-900 dark:text-white line-clamp-1">{{ storyTitle }}</h3>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ storyAuthor }}</p>
        <div class="flex gap-2 mt-1">
          <span class="text-xs text-zinc-500">{{ storyLastChapter }}</span>
          <span class="text-xs text-zinc-500">📊 {{ formatNumber(storyViews) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'

interface Props {
  story: Manga
  variant?: 'grid' | 'list' | 'ranked'
  rank?: number
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'grid',
  rank: 1
})

// Computed properties to handle both old and new format
const storyTitle = computed(() => props.story.name || props.story.title || 'Untitled')
const storyAuthor = computed(() => {
  if (typeof props.story.author === 'string') return props.story.author
  return props.story.author?.name || 'Unknown'
})
const storyCoverImage = computed(() => props.story.avatar)
const storyCategories = computed(() => {
  if (!props.story.categories) return []
  if (typeof props.story.categories[0] === 'string') return props.story.categories as string[]
  return (props.story.categories as Array<{ name: string }>).map(c => c.name)
})
const storyLastChapter = computed(() => {
  if (props.story.lastChapter) return props.story.lastChapter
  if (props.story.chapters && props.story.chapters.length > 0) {
    const lastChapter = props.story.chapters[props.story.chapters.length - 1]
    return lastChapter?.name || 'Chưa có chương'
  }
  return 'Chưa có chương'
})
const storyViews = computed(() => props.story.views || 0)
const storyRating = computed(() => props.story.rating || 0)
const storyStatus = computed(() => props.story.status || 'Đang ra')

const formatNumber = (num: number) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

const getLastChapterNumber = (lastChapter: string | undefined | null) => {
  if (!lastChapter) return '1'
  const match = lastChapter.match(/\d+/)
  return match ? match[0] : '1'
}
</script>
