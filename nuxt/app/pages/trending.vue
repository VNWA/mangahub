<template>
  <div>
    <!-- Hero -->
    <section class="bg-gradient-to-r from-red-600 to-pink-600 text-white py-8 md:py-12">
      <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold mb-4 flex items-center gap-2">
          <UIcon name="i-heroicons-fire" class="w-8 h-8 md:w-10 md:h-10" />
          Truyện Hot Nhất
        </h1>
        <p class="text-base md:text-lg text-red-100">Những bộ truyện được yêu thích nhất của độc giả</p>
      </div>
    </section>

    <!-- Comics Grid -->
    <section class="max-w-7xl mx-auto px-4 py-8 md:py-12">
      <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <ComicCard
          v-for="comic in hotComics"
          :key="comic.id"
          :comic="comic"
        />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
useHead({
  title: 'Truyện Hot Nhất - WebTruyện',
  meta: [
    { name: 'description', content: 'Danh sách truyện tranh hot nhất được độc giả yêu thích nhiều nhất trên WebTruyện.' },
    { name: 'robots', content: 'index, follow' }
  ]
})

const hotComics = ref([
  {
    id: '1',
    title: 'One Piece',
    author: 'Eiichiro Oda',
    thumbnail: 'https://via.placeholder.com/200x300?text=One+Piece',
    status: 'Đang cập nhật',
    rating: 9.8,
    categories: ['Phiêu lưu', 'Hành động'],
    lastChapter: 'Chapter 1120'
  },
  {
    id: '2',
    title: 'Naruto',
    author: 'Masashi Kishimoto',
    thumbnail: 'https://via.placeholder.com/200x300?text=Naruto',
    status: 'Hoàn thành',
    rating: 9.6,
    categories: ['Hành động', 'Ninja'],
    lastChapter: 'Chapter 700'
  },
  {
    id: '3',
    title: 'Dragon Ball',
    author: 'Akira Toriyama',
    thumbnail: 'https://via.placeholder.com/200x300?text=Dragon+Ball',
    status: 'Hoàn thành',
    rating: 9.5,
    categories: ['Hành động', 'Phiêu lưu'],
    lastChapter: 'Chapter 519'
  },
  {
    id: '4',
    title: 'Bleach',
    author: 'Tite Kubo',
    thumbnail: 'https://via.placeholder.com/200x300?text=Bleach',
    status: 'Đang cập nhật',
    rating: 9.3,
    categories: ['Hành động', 'Siêu nhiên'],
    lastChapter: 'Chapter 686'
  },
  {
    id: '5',
    title: 'Jujutsu Kaisen',
    author: 'Gege Akutami',
    thumbnail: 'https://via.placeholder.com/200x300?text=Jujutsu+Kaisen',
    status: 'Đang cập nhật',
    rating: 9.5,
    categories: ['Hành động', 'Siêu nhiên'],
    lastChapter: 'Chapter 250'
  },
  {
    id: '6',
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
