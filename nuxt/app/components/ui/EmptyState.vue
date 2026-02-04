<template>
  <div class="space-y-4">
    <!-- Empty State -->
    <div class="text-center py-12">
      <UIcon :name="icon" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">
        {{ title }}
      </h3>
      <p class="text-zinc-600 dark:text-zinc-400 mb-6 max-w-md mx-auto">
        {{ description }}
      </p>
      <NuxtLink v-if="actionLink" :to="actionLink"
        class="inline-block px-6 py-2 bg-primary text-white rounded-lg font-semibold hover:opacity-80 transition-opacity">
        {{ actionText }}
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  icon?: string
  title: string
  description: string
  actionText?: string
  actionLink?: string
}

withDefaults(defineProps<Props>(), {
  icon: 'i-heroicons-inbox',
})
</script>
