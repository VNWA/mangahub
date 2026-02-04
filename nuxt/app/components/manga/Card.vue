<template>
  <article :class="classes">

    <NuxtLink :to="`/${manga.slug}`" class="relative overflow-hidden bg-gray-200 dark:bg-zinc-700 h-48 block">
      <NuxtImg :src="manga.avatar" :alt="`${manga.name} thumbnail`"
        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />

      <div
        class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
      </div>
      <div class="absolute top-2 right-2 z-10" v-if="manga.badge">
        <Badge :badge="manga.badge" variant="solid" size="sm" />
      </div>

      <div
        class="absolute bottom-2 left-2 z-10 bg-black/70 backdrop-blur-sm text-white px-2 py-1 rounded-md flex items-center gap-1">
        <UIcon name="i-heroicons-star-solid" class="w-4 h-4 text-yellow-400" />
        <span class="text-sm font-semibold">{{ manga.rating }}</span>
      </div>
    </NuxtLink>

    <div class="p-4 flex-1 flex flex-col">
      <NuxtLink :to="`/${manga.slug}`">
        <h3
          class="text-md font-bold text-zinc-900 dark:text-white line-clamp-2 mb-2 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
          {{ manga.name }}
        </h3>
      </NuxtLink>
      <ul class="space-y-2">
        <li v-for="(item, index) in manga.chapters" :key="index">
          <NuxtLink :to="`/${manga.slug}/${item.slug}`">
            <div class="
               rounded-md px-2 py-1 flex justify-between 
               items-center  transition-colors  group/chapter
            ring-1 ring-zinc-200 dark:ring-zinc-700
            hover:bg-zinc-100 dark:hover:bg-zinc-600 
               ">
              <span class="text-sm text-zinc-900 dark:text-white">{{ item.name }}</span>
              <span v-show="item.created_at" class="text-xs text-zinc-500 dark:text-zinc-400">{{
                $formatTime(item.created_at || '') }} </span>
            </div>
          </NuxtLink>
        </li>
      </ul>
    </div>
  </article>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'

const props = defineProps<{
  manga: Manga
  variant?: 'vertical' | 'horizontal'
}>()
const type = computed(() => props.variant || 'vertical')

const classes = computed(() => {
  return [
    'bg-white dark:bg-zinc-800 rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 group h-full flex',
    type.value === 'vertical' ? 'flex-col' : 'flex-row'
  ]
})
</script>
