<template>
  <section class="max-w-7xl mx-auto px-4 py-12">
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">{{ title }}</h2>
      <p class="text-zinc-600 dark:text-zinc-400">{{ subtitle }}</p>
    </div>

    <!-- Horizontal Scroll Container -->
    <div ref="scrollContainer" class="flex gap-6 overflow-x-auto pb-4 snap-x snap-mandatory scroll-smooth"
      :style="{ scrollBehavior: 'smooth' }">
      <div v-for="comic in items" :key="comic.id" class="flex-shrink-0 w-40 snap-center">
        <ComicCardVariant :comic="comic" variant="compact" />
      </div>
    </div>

    <!-- Scroll Buttons -->
    <div class="flex gap-2 mt-4 justify-end">
      <UButton icon="i-heroicons-chevron-left" color="neutral" variant="soft" @click="scrollLeft" />
      <UButton icon="i-heroicons-chevron-right" color="neutral" variant="soft" @click="scrollRight" />
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'

interface Props {
  items: Manga[]
  title: string
  subtitle?: string
}

withDefaults(defineProps<Props>(), {
  subtitle: ''
})

const scrollContainer = ref<HTMLElement>()

const scrollLeft = () => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollLeft -= 300
  }
}

const scrollRight = () => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollLeft += 300
  }
}
</script>
