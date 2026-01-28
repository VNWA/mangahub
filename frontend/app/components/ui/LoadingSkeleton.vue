<template>
  <div :style="{ animationDuration: `${duration}ms` }" class="animate-pulse">
    <!-- Card Skeleton -->
    <div class="bg-slate-200 dark:bg-slate-700 rounded-lg h-48 mb-3"></div>
    
    <!-- Title -->
    <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
    
    <!-- Author -->
    <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-2/3"></div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  duration?: number
}

withDefaults(defineProps<Props>(), {
  duration: 1000
})
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse infinite;
}
</style>
