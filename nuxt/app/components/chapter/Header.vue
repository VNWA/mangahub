<template>
  <div v-if="chapter" class="mb-8 pb-8 border-b border-zinc-200 dark:border-zinc-700">
    <h1 class="text-3xl md:text-4xl font-bold mb-2">
      {{ chapter.title }}
    </h1>
    <div class="flex items-center justify-between text-sm text-zinc-600 dark:text-zinc-400">
      <span>{{ formatDate(chapter.uploadedAt) }}</span>
      <NuxtLink :to="`/${mangaSlug}`" class="text-primary hover:underline">
        ← Về trang truyện
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  chapter: {
    title: string
    uploadedAt: string
  } | null
  mangaSlug: string
}

defineProps<Props>()

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>
