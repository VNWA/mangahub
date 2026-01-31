<template>
  <div class="flex items-center gap-3">
    <div class="relative">
      <img
        v-if="src"
        :src="src"
        :alt="alt"
        :class="[
          'rounded-full object-cover',
          sizeClasses[size]
        ]"
      />
      <div
        v-else
        :class="[
          'rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold',
          sizeClasses[size]
        ]"
      >
        {{ initials }}
      </div>
      <div
        v-if="status"
        :class="[
          'absolute bottom-0 right-0 rounded-full border-2 border-white',
          statusClasses[status]
        ]"
      ></div>
    </div>
    <div v-if="showName" class="flex-1">
      <p class="font-semibold text-slate-900 dark:text-white">{{ name }}</p>
      <p v-if="subtitle" class="text-xs text-slate-600 dark:text-slate-400">{{ subtitle }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
type Size = 'xs' | 'sm' | 'md' | 'lg' | 'xl'
type Status = 'online' | 'offline' | 'away'

interface Props {
  src?: string
  alt?: string
  name?: string
  subtitle?: string
  showName?: boolean
  size?: Size
  status?: Status
  initials?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  showName: false,
  initials: 'U'
})

const sizeClasses = {
  xs: 'w-6 h-6 text-xs',
  sm: 'w-8 h-8 text-sm',
  md: 'w-10 h-10',
  lg: 'w-12 h-12',
  xl: 'w-16 h-16'
}

const statusClasses = {
  online: 'w-3 h-3 bg-green-500',
  offline: 'w-3 h-3 bg-gray-400',
  away: 'w-3 h-3 bg-yellow-500'
}
</script>
