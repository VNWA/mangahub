<template>
  <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 space-y-6">
    <!-- Cover Image -->
    <div class="relative rounded-lg overflow-hidden bg-slate-200 dark:bg-slate-700 h-80">
      <img
        v-if="story.coverImage"
        :src="story.coverImage"
        :alt="story.title"
        class="w-full h-full object-cover"
      />
      <div v-else class="w-full h-full flex items-center justify-center">
        <UIcon name="i-heroicons-photo" class="w-20 h-20 text-gray-400" />
      </div>

      <!-- Status Badge -->
      <div class="absolute top-4 right-4">
        <UBadge
          :label="story.status"
          :color="story.status === 'Đang ra' ? 'success' : 'secondary'"
          size="lg"
        />
      </div>
    </div>

    <!-- Title -->
    <div>
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ story.title }}</h1>
      <p class="text-lg text-slate-600 dark:text-slate-400">{{ story.author }}</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
      <div class="text-center">
        <div class="flex items-center justify-center gap-1 mb-1">
          <UIcon name="i-heroicons-star-solid" class="w-5 h-5 text-yellow-400" />
          <span class="text-2xl font-bold">{{ story.rating }}</span>
        </div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Rating</p>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold mb-1">{{ formatNumber(story.views) }}</div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Lượt xem</p>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold mb-1">{{ story.chapters?.length || 0 }}</div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Chương</p>
      </div>
    </div>

    <!-- Categories -->
    <div>
      <p class="text-sm font-semibold text-slate-900 dark:text-white mb-2">Thể loại</p>
      <div class="flex flex-wrap gap-2">
        <UBadge
          v-for="cat in story.categories"
          :key="cat"
          :label="cat"
          variant="soft"
          size="sm"
        />
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-3">
      <UButton
        color="primary"
        icon="i-heroicons-play"
        label="Đọc từ đầu"
        block
        size="lg"
        @click="readFromStart"
      />
      <UButton
        :icon="isFavorited ? 'i-heroicons-heart-solid' : 'i-heroicons-heart'"
        :color="isFavorited ? 'error' : 'neutral'"
        :variant="isFavorited ? 'soft' : 'ghost'"
        square
        size="lg"
        @click="toggleFavorite"
      />
    </div>

    <!-- Description -->
    <div>
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-slate-900 dark:text-white">Mô tả</h3>
        <button
          @click="descriptionExpanded = !descriptionExpanded"
          class="text-sm text-primary"
        >
          {{ descriptionExpanded ? 'Ẩn' : 'Xem thêm' }}
        </button>
      </div>
      <p
        :class="[
          'text-slate-700 dark:text-slate-300 text-sm leading-relaxed',
          !descriptionExpanded && 'line-clamp-3'
        ]"
      >
        {{ story.description }}
      </p>
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 border-t border-slate-200 dark:border-slate-700 pt-4">
      <div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Trạng thái</p>
        <p class="font-semibold">{{ story.status }}</p>
      </div>
      <div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Cập nhật</p>
        <p class="font-semibold">{{ story.lastUpdate }}</p>
      </div>
      <div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Chương mới</p>
        <p class="font-semibold">{{ story.lastChapter }}</p>
      </div>
      <div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Độ dài</p>
        <p class="font-semibold">{{ story.chapters?.length || 0 }} tập</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Chapter {
  id: string
  number: number
  title: string
  url: string
  views: number
  uploadedAt: string
}

interface StoryInfoProps {
  story: {
    id: string
    title: string
    slug: string
    author: string
    coverImage?: string
    status: string
    rating: number
    categories: string[]
    description: string
    views: number
    lastChapter: string
    lastUpdate: string
    chapters?: Chapter[]
  }
}

defineProps<StoryInfoProps>()

const isFavorited = ref(false)
const descriptionExpanded = ref(false)

const formatNumber = (num: number) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

const readFromStart = () => {
  // Navigate to first chapter
}

const toggleFavorite = () => {
  isFavorited.value = !isFavorited.value
}
</script>
