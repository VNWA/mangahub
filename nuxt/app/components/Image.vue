<template>
  <div :class="['relative overflow-hidden rounded-lg', containerClass]">
    <img
      v-if="src"
      :src="src"
      :alt="alt"
      :loading="lazy ? 'lazy' : 'eager'"
      :class="[
        'w-full h-full object-cover transition-transform duration-300',
        hoverable && 'group-hover:scale-110'
      ]"
    />
    <div v-else class="w-full h-full bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center">
      <UIcon name="i-heroicons-photo" class="w-12 h-12 text-slate-400" />
    </div>

    <!-- Overlay -->
    <div
      v-if="showOverlay"
      class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3"
    >
      <UButton
        v-if="showEdit"
        icon="i-heroicons-pencil"
        color="primary"
        variant="ghost"
        size="sm"
        square
      />
      <UButton
        v-if="showDelete"
        icon="i-heroicons-trash"
        color="error"
        variant="ghost"
        size="sm"
        square
      />
    </div>

    <!-- Badge -->
    <div v-if="badge" class="absolute top-2 right-2 z-10">
      <UBadge :label="badge" size="sm" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  src?: string
  alt?: string
  lazy?: boolean
  hoverable?: boolean
  showOverlay?: boolean
  showEdit?: boolean
  showDelete?: boolean
  badge?: string
  containerClass?: string
  aspectRatio?: 'square' | '16/9' | '4/3'
}

const props = withDefaults(defineProps<Props>(), {
  alt: 'Image',
  lazy: true,
  hoverable: false,
  showOverlay: false,
  aspectRatio: 'square',
  containerClass: 'h-64 group'
})

const containerClass = computed(() => {
  const ratio = {
    'square': 'aspect-square',
    '16/9': 'aspect-video',
    '4/3': 'aspect-[4/3]'
  }
  return ratio[props.aspectRatio] || ratio.square
})
</script>
