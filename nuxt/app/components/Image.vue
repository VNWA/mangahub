<template>
  <NuxtImg :src="src" :alt="alt" :format="format" :quality="quality" :loading="loading" />
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  src: string
  alt: string
  format?: 'webp' | 'avif' | 'jpg' | 'png'
  quality?: number
  loading?: 'lazy' | 'eager'
}>(), {
  src: 'https://via.placeholder.com/150',
  alt: 'Image',
  format: 'webp',
  quality: 80,
  loading: 'eager',
});
</script>
