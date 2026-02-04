<template>
  <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 text-center">
    <!-- Avatar -->
    <div class="mb-4">
      <img :src="user.avatar ?? ''" :alt="user.name" class="w-20 h-20 rounded-full mx-auto border-4 border-primary" />
    </div>

    <!-- User Info -->
    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ user.name }}</h2>
    <p class="text-zinc-600 dark:text-zinc-400 text-sm mb-4">{{ user.email }}</p>

    <!-- Coin Balance -->
    <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
      <div class="flex items-center justify-center gap-2">
        <UIcon name="i-heroicons-currency-dollar" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
        <span class="text-lg font-bold text-yellow-700 dark:text-yellow-300">
          {{ formatCoin(user.coin || 0) }} coin
        </span>
      </div>
    </div>

    <!-- Stats -->
    <!-- <div class="grid grid-cols-3 gap-4 mb-6 py-4 border-y border-zinc-200 dark:border-zinc-700">
      <div>
        <div class="text-2xl font-bold text-primary">{{ user.favorites?.length ?? 0 }}</div>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Yêu thích</p>
      </div>
      <div>
        <div class="text-2xl font-bold text-primary">{{ user.readingHistory?.length ?? 0 }}</div>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Đang đọc</p>
      </div>
      <div>
        <div class="text-2xl font-bold text-primary">{{ user.followers?.length ?? 0 }}</div>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Theo dõi</p>
      </div>
    </div> -->

    <!-- Action Buttons -->
    <div class="flex gap-2">
      <UButton color="primary" icon="i-heroicons-pencil" label="Chỉnh sửa" block @click="isEditing = true" />
      <UButton color="neutral" icon="i-heroicons-cog-6-tooth" square />
    </div>

    <!-- Member Since -->
    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-4">
      Tham gia: {{ user.created_at ? new Date(user.created_at).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      }) : '' }}
    </p>
  </div>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const user = auth.user
const isEditing = ref(false)

const formatCoin = (coin: number) => {
  return new Intl.NumberFormat('vi-VN').format(coin)
}
</script>
