<template>
  <div>
    <nav class="max-w-7xl mx-auto px-4 py-4">
      <ul class="flex gap-2 text-sm">
        <li>
          <NuxtLink to="/" class="text-blue-600 hover:underline">Trang chủ</NuxtLink>
        </li>
        <li class="text-zinc-600">/</li>
        <li>
          <NuxtLink to="/category/list" class="text-blue-600 hover:underline">Thể loại</NuxtLink>
        </li>
        <li class="text-zinc-600">/</li>
        <li class="text-zinc-900 dark:text-white font-semibold">{{ categoryName }}</li>
      </ul>
    </nav>
    <section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-12">
      <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-4xl font-bold mb-2">{{ categoryName }}</h1>
        <p class="text-lg text-blue-100">{{ comicCount }} bộ truyện</p>
      </div>
    </section>


    <section class="max-w-7xl mx-auto px-4 py-12">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <MangaCard v-for="comic in categoryComics" :key="comic.id" :manga="comic" />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'

const route = useRoute()
const categoryId = route.params.id as string

const categoryMap: Record<string, { name: string; count: number }> = {
  'action': { name: 'Hành Động', count: 245 },
  'romance': { name: 'Tình Cảm', count: 189 },
  'comedy': { name: 'Hài Hước', count: 156 },
  'fantasy': { name: 'Kỳ Ảo', count: 234 },
  'mystery': { name: 'Bí Ẩn', count: 123 },
  'horror': { name: 'Kinh Dị', count: 89 }
}

const categoryName = computed(() => categoryMap[categoryId]?.name || 'Thể loại')
const comicCount = computed(() => categoryMap[categoryId]?.count || 0)

const categoryComics = ref<Manga[]>([
  {
    id: '1',
    slug: 'jujutsu-kaisen',
    title: 'Jujutsu Kaisen',
    author: 'Gege Akutami',
    thumbnail: 'https://via.placeholder.com/200x300?text=Jujutsu+Kaisen',
    status: 'Đang cập nhật',
    rating: 9.5,
    categories: ['Hành động', 'Siêu nhiên'],
    lastChapter: 'Chapter 250'
  },
  {
    id: '2',
    slug: 'demon-slayer',
    title: 'Demon Slayer',
    author: 'Koyoharu Gotouge',
    thumbnail: 'https://via.placeholder.com/200x300?text=Demon+Slayer',
    status: 'Hoàn thành',
    rating: 9.4,
    categories: ['Hành động', 'Kỳ ảo'],
    lastChapter: 'Chapter 205'
  },
  {
    id: '3',
    slug: 'attack-on-titan',
    title: 'Attack on Titan',
    author: 'Hajime Isayama',
    thumbnail: 'https://via.placeholder.com/200x300?text=Attack+on+Titan',
    status: 'Hoàn thành',
    rating: 9.7,
    categories: ['Hành động', 'Bí ẩn'],
    lastChapter: 'Chapter 139'
  },
  {
    id: '4',
    slug: 'my-hero-academia',
    title: 'My Hero Academia',
    author: 'Kohei Horikoshi',
    thumbnail: 'https://via.placeholder.com/200x300?text=My+Hero',
    status: 'Đang cập nhật',
    rating: 9.2,
    categories: ['Hành động', 'Siêu năng lực'],
    lastChapter: 'Chapter 430'
  }
])
</script>
