<template>
  <aside class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
    <!-- Categories -->
    <div class="mb-8">
      <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Thể loại</h3>
      <ul class="space-y-2">
        <li v-for="category in categories" :key="category">
          <NuxtLink
            :to="`/category/${category.id}`"
            class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
          >
            {{ category.name }}
          </NuxtLink>
        </li>
      </ul>
    </div>

    <!-- Hot Comics -->
    <div class="mb-8">
      <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Truyện Hot</h3>
      <ul class="space-y-3">
        <li v-for="comic in hotComics" :key="comic.id" class="flex gap-3">
          <img
            v-if="comic.thumbnail"
            :src="comic.thumbnail"
            :alt="comic.title"
            class="w-12 h-16 rounded object-cover"
          />
          <div class="flex-1">
            <NuxtLink
              :to="`/comic/${comic.id}`"
              class="text-sm font-semibold text-slate-900 dark:text-white hover:text-blue-600 line-clamp-2"
            >
              {{ comic.title }}
            </NuxtLink>
            <div class="flex items-center gap-1 mt-1">
              <UIcon name="i-heroicons-star" class="w-4 h-4 text-yellow-500" />
              <span class="text-xs text-slate-600 dark:text-slate-400">{{ comic.rating }}</span>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Subscribe Banner -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-lg p-4">
      <h4 class="font-bold mb-2">Theo dõi mới nhất</h4>
      <p class="text-sm mb-3">Nhận thông báo khi có chương mới</p>
      <UButton block color="primary" variant="soft" label="Đăng ký" size="sm" />
    </div>
  </aside>
</template>

<script setup lang="ts">
const categories = [
  { id: 'action', name: 'Hành động' },
  { id: 'romance', name: 'Tình cảm' },
  { id: 'comedy', name: 'Hài hước' },
  { id: 'fantasy', name: 'Kỳ ảo' },
  { id: 'mystery', name: 'Bí ẩn' },
  { id: 'horror', name: 'Kinh dị' }
]

const hotComics = ref([
  {
    id: '1',
    title: 'Tên truyện hot nhất',
    thumbnail: 'https://via.placeholder.com/50x70',
    rating: '9.5'
  },
  {
    id: '2',
    title: 'Truyện hay thứ hai',
    thumbnail: 'https://via.placeholder.com/50x70',
    rating: '9.3'
  },
  {
    id: '3',
    title: 'Truyện phổ biến',
    thumbnail: 'https://via.placeholder.com/50x70',
    rating: '9.1'
  }
])
</script>
