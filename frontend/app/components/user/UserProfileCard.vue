<template>
  <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 text-center">
    <!-- Avatar -->
    <div class="mb-4">
      <img
        :src="user.avatar"
        :alt="user.username"
        class="w-20 h-20 rounded-full mx-auto border-4 border-primary"
      />
    </div>

    <!-- User Info -->
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ user.username }}</h2>
    <p class="text-slate-600 dark:text-slate-400 text-sm mb-2">{{ user.email }}</p>
    <p class="text-slate-700 dark:text-slate-300 text-sm mb-6">{{ user.bio }}</p>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6 py-4 border-y border-slate-200 dark:border-slate-700">
      <div>
        <div class="text-2xl font-bold text-primary">{{ user.favoriteCount }}</div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Yêu thích</p>
      </div>
      <div>
        <div class="text-2xl font-bold text-primary">{{ user.readingCount }}</div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Đang đọc</p>
      </div>
      <div>
        <div class="text-2xl font-bold text-primary">{{ user.followersCount }}</div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Theo dõi</p>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-2">
      <UButton
        color="primary"
        icon="i-heroicons-pencil"
        label="Chỉnh sửa"
        block
        @click="isEditing = true"
      />
      <UButton
        color="neutral"
        icon="i-heroicons-cog-6-tooth"
        square
      />
    </div>

    <!-- Member Since -->
    <p class="text-xs text-slate-600 dark:text-slate-400 mt-4">
      Tham gia: {{ formatDate(user.createdAt) }}
    </p>
  </div>
</template>

<script setup lang="ts">
interface Props {
  user: {
    id: string
    username: string
    email: string
    avatar: string
    bio: string
    favoriteCount: number
    readingCount: number
    followersCount: number
    createdAt: string
  }
}

const props = defineProps<Props>()

const isEditing = ref(false)

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>
