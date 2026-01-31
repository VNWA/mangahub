<template>
  <div>
    <!-- Hero -->
    <section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-8 md:py-12">
      <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold mb-4">Tất Cả Thể Loại</h1>
        <p class="text-base md:text-lg text-blue-100">Khám phá truyện tranh theo thể loại ưa thích của bạn</p>
      </div>
    </section>

    <!-- Categories Grid -->
    <section class="max-w-7xl mx-auto px-4 py-8 md:py-12">
      <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <NuxtLink
          v-for="category in categories"
          :key="category.id"
          :to="`/category/${category.id}`"
          class="group relative bg-white dark:bg-slate-800 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-all"
        >
          <div class="h-40 bg-gradient-to-br from-blue-500 to-purple-500 group-hover:from-blue-600 group-hover:to-purple-600 transition-colors flex items-center justify-center">
            <UIcon :name="category.icon" class="w-16 h-16 text-white" />
          </div>
          <div class="p-4">
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">{{ category.name }}</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ category.count }} truyện</p>
          </div>
        </NuxtLink>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
useHead({
  title: 'Thể Loại Truyện - WebTruyện',
  meta: [
    { name: 'description', content: 'Khám phá các thể loại truyện tranh: Hành động, Tình cảm, Hài hước, Kỳ ảo, Kinh dị và nhiều thể loại khác.' },
    { name: 'robots', content: 'index, follow' }
  ]
})

const categories = [
  { id: 'action', name: 'Hành Động', icon: 'i-heroicons-bolt', count: 245 },
  { id: 'romance', name: 'Tình Cảm', icon: 'i-heroicons-heart', count: 189 },
  { id: 'comedy', name: 'Hài Hước', icon: 'i-heroicons-face-smile', count: 156 },
  { id: 'fantasy', name: 'Kỳ Ảo', icon: 'i-heroicons-sparkles', count: 234 },
  { id: 'mystery', name: 'Bí Ẩn', icon: 'i-heroicons-question-mark-circle', count: 123 },
  { id: 'horror', name: 'Kinh Dị', icon: 'i-heroicons-exclamation-triangle', count: 89 },
  { id: 'scifi', name: 'Sci-Fi', icon: 'i-heroicons-globe-alt', count: 178 },
  { id: 'slice-of-life', name: 'Slice of Life', icon: 'i-heroicons-sun', count: 145 }
]
</script>
