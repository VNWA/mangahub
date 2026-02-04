<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-12">
    <UCard v-if="previousChapter" :to="`/${mangaSlug}/${previousChapter.id}`"
      class="hover:shadow-lg transition-all cursor-pointer">
      <div class="flex items-center gap-3">
        <UIcon name="i-heroicons-arrow-left" class="w-5 h-5 text-primary" />
        <div>
          <p class="text-xs text-zinc-600 dark:text-zinc-400">Chương trước</p>
          <p class="font-semibold text-zinc-900 dark:text-white">{{ previousChapter.title }}</p>
        </div>
      </div>
    </UCard>
    <div v-else></div>

    <UCard v-if="nextChapter" :to="`/${mangaSlug}/${nextChapter.id}`"
      class="hover:shadow-lg transition-all cursor-pointer text-right">
      <div class="flex items-center justify-end gap-3">
        <div>
          <p class="text-xs text-zinc-600 dark:text-zinc-400">Chương tiếp</p>
          <p class="font-semibold text-zinc-900 dark:text-white">{{ nextChapter.title }}</p>
        </div>
        <UIcon name="i-heroicons-arrow-right" class="w-5 h-5 text-primary" />
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
interface Props {
  previousChapter: {
    id: string
    title: string
  } | null
  nextChapter: {
    id: string
    title: string
  } | null
  mangaSlug: string
}

defineProps<Props>()
</script>
