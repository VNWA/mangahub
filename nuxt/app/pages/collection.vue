<template>
  <div>
    <!-- Header -->
    <section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-6 md:py-8">
      <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">Bộ Sưu Tập</h1>
        <p class="text-sm md:text-base text-blue-100">Tìm kiếm và khám phá truyện tranh yêu thích</p>
      </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-6 md:py-12">
      <div class="grid md:grid-cols-4 gap-6 md:gap-8">
        <!-- Sidebar -->
        <div class="md:col-span-1">
          <div class="sticky top-4">
            <FilterSidebar @apply="handleFilterApply" @reset="handleFilterReset" />
          </div>
        </div>

        <!-- Comics Grid -->
        <div class="md:col-span-3">
          <!-- View Toggle -->
          <div class="flex items-center justify-between mb-8">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
              Hiển thị {{ filteredComics.length }} kết quả
            </div>
            <div class="flex gap-2">
              <UButton icon="i-heroicons-list-bullet" :color="viewMode === 'list' ? 'primary' : 'neutral'"
                variant="ghost" square @click="viewMode = 'list'" />
              <UButton icon="i-heroicons-square-3-stack-3d" :color="viewMode === 'grid' ? 'primary' : 'neutral'"
                variant="ghost" square @click="viewMode = 'grid'" />
            </div>
          </div>

          <!-- Comics Display -->
          <div :class="[
            'gap-6',
            viewMode === 'grid'
              ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
              : 'space-y-4'
          ]">
            <ComicCardVariant v-for="comic in paginatedComics" :key="comic.id" :comic="comic"
              :variant="viewMode === 'grid' ? 'grid' : 'list'" />
          </div>

          <!-- Pagination -->
          <div class="flex items-center justify-center gap-2 mt-12">
            <UButton icon="i-heroicons-chevron-left" color="neutral" variant="ghost"
              @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1" />
            <div class="flex gap-1">
              <UButton v-for="page in totalPages" :key="page" :label="`${page}`"
                :color="currentPage === page ? 'info' : 'neutral'" :variant="currentPage === page ? 'soft' : 'ghost'"
                size="sm" @click="currentPage = page" />
            </div>
            <UButton icon="i-heroicons-chevron-right" color="neutral" variant="ghost"
              @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'

useHead({
  title: 'Bộ Sưu Tập Truyện - WebTruyện',
  meta: [
    { name: 'description', content: 'Khám phá bộ sưu tập truyện tranh với các bộ lọc tìm kiếm nâng cao.' }
  ]
})

const viewMode = ref<'grid' | 'list'>('grid')
const currentPage = ref(1)
const itemsPerPage = 12

const allComics = ref<Manga[]>([
  {
    id: '1',
    slug: 'jujutsu-kaisen',
    title: 'Jujutsu Kaisen',
    author: 'Gege Akutami',
    thumbnail: 'https://via.placeholder.com/200x300?text=Jujutsu+Kaisen',
    status: 'Đang cập nhật',
    rating: 9.5,
    categories: ['Hành động', 'Siêu nhiên'],
    lastChapter: 'Chapter 250',
    description: 'Một bộ truyện hấp dẫn về cuộc chiến với những con quái vật.',
    views: 1200000
  },
  {
    id: '2',
    slug: 'one-piece',
    title: 'One Piece',
    author: 'Eiichiro Oda',
    thumbnail: 'https://via.placeholder.com/200x300?text=One+Piece',
    status: 'Đang cập nhật',
    rating: 9.8,
    categories: ['Phiêu lưu', 'Hành động'],
    lastChapter: 'Chapter 1120',
    description: 'Câu chuyện về cải tạo và tìm kiếm kho báu.',
    views: 2500000
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
    lastChapter: 'Chapter 139',
    description: 'Cuộc chiến giữa nhân loại và những sinh vật khổng lồ.',
    views: 1800000
  },
  {
    id: '4',
    slug: 'demon-slayer',
    title: 'Demon Slayer',
    author: 'Koyoharu Gotouge',
    thumbnail: 'https://via.placeholder.com/200x300?text=Demon+Slayer',
    status: 'Hoàn thành',
    rating: 9.4,
    categories: ['Hành động', 'Kỳ ảo'],
    lastChapter: 'Chapter 205',
    description: 'Cuộc hành trình trả thù của một thanh niên.',
    views: 1600000
  },
  {
    id: '5',
    slug: 'my-hero-academia',
    title: 'My Hero Academia',
    author: 'Kohei Horikoshi',
    thumbnail: 'https://via.placeholder.com/200x300?text=My+Hero',
    status: 'Đang cập nhật',
    rating: 9.2,
    categories: ['Hành động', 'Siêu năng lực'],
    lastChapter: 'Chapter 430',
    description: 'Câu chuyện về những siêu anh hùng tiến bộ.',
    views: 2000000
  },
  {
    id: '6',
    slug: 'tokyo-ghoul',
    title: 'Tokyo Ghoul',
    author: 'Sui Ishida',
    thumbnail: 'https://via.placeholder.com/200x300?text=Tokyo+Ghoul',
    status: 'Hoàn thành',
    rating: 9.1,
    categories: ['Bí ẩn', 'Kinh dị'],
    lastChapter: 'Chapter 179',
    description: 'Câu chuyện tối tăm về những sinh vật lạ.',
    views: 1400000
  },
  {
    id: '7',
    title: 'Fullmetal Alchemist',
    author: 'Hiromu Arakawa',
    thumbnail: 'https://via.placeholder.com/200x300?text=FMA',
    status: 'Hoàn thành',
    rating: 9.8,
    categories: ['Kỳ ảo', 'Hành động'],
    lastChapter: 'Chapter 116',
    description: 'Hai anh em đi tìm viên đá lành mạnh.',
    views: 1300000
  },
  {
    id: '8',
    title: 'Bleach',
    author: 'Tite Kubo',
    thumbnail: 'https://via.placeholder.com/200x300?text=Bleach',
    status: 'Đang cập nhật',
    rating: 9.3,
    categories: ['Hành động', 'Siêu nhiên'],
    lastChapter: 'Chapter 686',
    description: 'Cuộc chiến giữa thế giới người sống và người chết.',
    views: 1500000
  }
])

const filteredComics = computed(() => {
  return allComics.value
})

const totalPages = computed(() => Math.ceil(filteredComics.value.length / itemsPerPage))

const paginatedComics = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredComics.value.slice(start, start + itemsPerPage)
})

const handleFilterApply = (filters: any) => {
  console.log('Applied filters:', filters)
}

const handleFilterReset = () => {
  console.log('Filters reset')
  currentPage.value = 1
}
</script>
