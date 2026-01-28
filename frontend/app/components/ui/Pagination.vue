<template>
  <div class="flex items-center justify-center gap-2">
    <!-- Previous Button -->
    <UButton
      icon="i-heroicons-arrow-left"
      color="neutral"
      variant="ghost"
      square
      :disabled="currentPage === 1"
      @click="$emit('previous')"
    />

    <!-- Page Numbers -->
    <div class="flex items-center gap-1">
      <button
        v-for="page in visiblePages"
        :key="page"
        @click="$emit('update', page)"
        :class="[
          'min-w-10 h-10 px-2 rounded-lg font-semibold transition-colors',
          page === currentPage
            ? 'bg-primary text-white'
            : 'hover:bg-slate-200 dark:hover:bg-slate-700'
        ]"
      >
        {{ page }}
      </button>
      <span v-if="showLastDots" class="px-2">...</span>
      <button
        v-if="totalPages > visiblePages[visiblePages.length - 1]"
        @click="$emit('update', totalPages)"
        :class="[
          'min-w-10 h-10 px-2 rounded-lg font-semibold transition-colors',
          totalPages === currentPage
            ? 'bg-primary text-white'
            : 'hover:bg-slate-200 dark:hover:bg-slate-700'
        ]"
      >
        {{ totalPages }}
      </button>
    </div>

    <!-- Next Button -->
    <UButton
      icon="i-heroicons-arrow-right"
      color="neutral"
      variant="ghost"
      square
      :disabled="currentPage === totalPages"
      @click="$emit('next')"
    />
  </div>
</template>

<script setup lang="ts">
interface Props {
  currentPage: number
  totalPages: number
  maxVisible?: number
}

const props = withDefaults(defineProps<Props>(), {
  maxVisible: 5
})

const emit = defineEmits<{
  'update': [page: number]
  'next': []
  'previous': []
}>()

const visiblePages = computed(() => {
  const pages: number[] = []
  const start = Math.max(1, props.currentPage - Math.floor(props.maxVisible / 2))
  const end = Math.min(props.totalPages, start + props.maxVisible - 1)

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const showLastDots = computed(() => {
  return visiblePages.value[visiblePages.value.length - 1] < props.totalPages - 1
})
</script>
