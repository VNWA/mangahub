<template>
  <USlideover v-model="isOpen" side="left">
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Danh sách chương</h3>
          <UButton color="neutral" variant="ghost" icon="i-heroicons-x-mark" square size="sm" @click="close" />
        </div>
      </template>

      <div class="space-y-2 max-h-[calc(100vh-200px)] overflow-y-auto">
        <NuxtLink v-for="ch in chapters" :key="ch.slug" :to="`/${mangaSlug}/${ch.slug}`" :class="[
          'block p-3 rounded-lg transition-colors',
          currentChapterSlug === ch.slug
            ? 'bg-primary text-white'
            : 'hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-900 dark:text-white'
        ]" @click="close">
          <div class="flex items-center justify-between">
            <span class="font-medium">{{ ch.name }}</span>
            <span v-if="currentChapterSlug === ch.slug" class="text-xs opacity-75">Đang đọc</span>
          </div>
        </NuxtLink>
      </div>
    </UCard>
  </USlideover>
</template>

<script setup lang="ts">
interface Chapter {
  id: number
  name: string
  slug: string
  order: number
}

interface Props {
  chapters: Chapter[]
  mangaSlug: string
  currentChapterSlug: string
}

defineProps<Props>()

const isOpen = defineModel<boolean>('modelValue')

const close = () => {
  isOpen.value = false
}
</script>
