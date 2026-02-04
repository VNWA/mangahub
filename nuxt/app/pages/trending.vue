<template>
  <div>
    <!-- Hero -->
    <section class="bg-linear-to-r from-red-600 to-pink-600 text-white py-8 md:py-12">
      <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold mb-4 flex items-center gap-2">
          <UIcon name="i-heroicons-fire" class="w-8 h-8 md:w-10 md:h-10" />
          Truyện Hot Nhất
        </h1>
        <p class="text-base md:text-lg text-red-100">Những bộ truyện được yêu thích nhất của độc giả</p>
      </div>
    </section>

    <!-- Grid -->
    <section class="max-w-7xl mx-auto px-4 py-8 md:py-12">
      <!-- Loading -->
      <div v-if="hotPending" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <div v-for="i in 12" :key="i" class="animate-pulse">
          <div class="aspect-3/4 bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="hotManga.length === 0" class="text-center py-16">
        <UIcon name="i-heroicons-fire" class="w-12 h-12 text-zinc-400 mx-auto mb-3" />
        <p class="text-zinc-600 dark:text-zinc-400">Chưa có dữ liệu truyện hot</p>
      </div>

      <!-- Data -->
      <div v-else class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <MangaCard v-for="manga in hotManga" :key="manga.id" :manga="manga" />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'

useHead({
  title: 'Truyện Hot Nhất - WebTruyện',
  meta: [
    { name: 'description', content: 'Danh sách truyện tranh hot nhất được độc giả yêu thích nhiều nhất trên WebTruyện.' },
    { name: 'robots', content: 'index, follow' }
  ]
})

const { data: hotData, pending: hotPending } = useLazyHttp<{ ok: boolean; data: Manga[] }>('/mangas/hot', {
  query: { limit: 24 }
})

const hotManga = computed(() => hotData.value?.data || [])
</script>
