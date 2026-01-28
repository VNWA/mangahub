<template>
  <div>
    <!-- Header -->
    <section class="bg-gradient-to-r from-pink-600 to-purple-600 text-white py-8 md:py-12">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <UIcon name="i-heroicons-heart" class="w-8 h-8" />
          </div>
          <div>
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Truyện Yêu Thích</h1>
            <p class="text-pink-100">Danh sách truyện bạn đã yêu thích</p>
          </div>
        </div>
        <div class="flex items-center gap-4 text-sm">
          <div class="flex items-center gap-2">
            <UIcon name="i-heroicons-book-open" class="w-5 h-5" />
            <span>{{ favorites.length }} truyện</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
      <!-- Empty State -->
      <div v-if="favorites.length === 0" class="text-center py-16">
        <div class="w-24 h-24 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
          <UIcon name="i-heroicons-heart" class="w-12 h-12 text-slate-400" />
        </div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Chưa có truyện yêu thích</h3>
        <p class="text-slate-600 dark:text-slate-400 mb-6">Hãy khám phá và thêm truyện vào danh sách yêu thích của bạn</p>
        <UButton to="/" color="primary" label="Khám phá ngay" icon="i-heroicons-arrow-right" />
      </div>

      <!-- Favorites Grid -->
      <div v-else class="space-y-6">
        <!-- View Toggle -->
        <div class="flex items-center justify-between">
          <p class="text-slate-600 dark:text-slate-400">
            Hiển thị {{ favorites.length }} truyện
          </p>
          <div class="flex gap-2">
            <UButton
              :color="viewMode === 'grid' ? 'primary' : 'neutral'"
              :variant="viewMode === 'grid' ? 'soft' : 'ghost'"
              icon="i-heroicons-squares-2x2"
              square
              size="sm"
              @click="viewMode = 'grid'"
            />
            <UButton
              :color="viewMode === 'list' ? 'primary' : 'neutral'"
              :variant="viewMode === 'list' ? 'soft' : 'ghost'"
              icon="i-heroicons-list-bullet"
              square
              size="sm"
              @click="viewMode = 'list'"
            />
          </div>
        </div>

        <!-- Grid View -->
        <div
          v-if="viewMode === 'grid'"
          class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6"
        >
          <StoryCard
            v-for="story in favorites"
            :key="story.id"
            :story="story"
            variant="grid"
          />
        </div>

        <!-- List View -->
        <div v-else class="space-y-4">
          <StoryCard
            v-for="story in favorites"
            :key="story.id"
            :story="story"
            variant="list"
          />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">


const viewMode = ref<'grid' | 'list'>('grid')

// Mock data - sẽ kết nối với API sau
const favorites = ref([
  {
    id: '1',
    title: 'One Piece',
    slug: 'one-piece',
    author: 'Oda Eiichiro',
    coverImage: 'https://images.unsplash.com/photo-1621038149384-90c0f91bb3f4?w=400&h=600&fit=crop',
    status: 'Đang ra',
    rating: 9.8,
    categories: ['Action', 'Adventure'],
    lastChapter: 'Chapter 1150',
    description: 'Câu chuyện về cuộc phiêu lưu tìm kiếm kho báu vĩ đại',
    views: 5000000
  },
  {
    id: '2',
    title: 'My Hero Academia',
    slug: 'my-hero-academia',
    author: 'Horikoshi Kohei',
    coverImage: 'https://images.unsplash.com/photo-1623956299424-58e5a4e3f4c7?w=400&h=600&fit=crop',
    status: 'Đang ra',
    rating: 9.5,
    categories: ['Action', 'Superpowers'],
    lastChapter: 'Chapter 430',
    description: 'Thế giới siêu anh hùng nơi hầu hết mọi người có khả năng đặc biệt',
    views: 4200000
  },
  {
    id: '3',
    title: 'Attack on Titan',
    slug: 'attack-on-titan',
    author: 'Isayama Hajime',
    coverImage: 'https://images.unsplash.com/photo-1634447288519-eec6d71f7ab3?w=400&h=600&fit=crop',
    status: 'Hoàn thành',
    rating: 9.7,
    categories: ['Action', 'Dark'],
    lastChapter: 'Chapter 139',
    description: 'Cuộc chiến sinh tử giữa con người và những sinh vật khổng lồ',
    views: 3800000
  },
  {
    id: '4',
    title: 'Jujutsu Kaisen',
    slug: 'jujutsu-kaisen',
    author: 'Akutami Gege',
    coverImage: 'https://images.unsplash.com/photo-1621038149384-90c0f91bb3f4?w=400&h=600&fit=crop',
    status: 'Đang ra',
    rating: 9.4,
    categories: ['Action', 'Supernatural'],
    lastChapter: 'Chapter 250',
    description: 'Một chàng trai thường loại trở thành chiến binh phép thuật',
    views: 3200000
  },
  {
    id: '5',
    title: 'Demon Slayer',
    slug: 'demon-slayer',
    author: 'Gotouge Koyoharu',
    coverImage: 'https://images.unsplash.com/photo-1634447288519-eec6d71f7ab3?w=400&h=600&fit=crop',
    status: 'Hoàn thành',
    rating: 9.3,
    categories: ['Action', 'Fantasy'],
    lastChapter: 'Chapter 205',
    description: 'Hành trình trả thù của một thanh niên thường loại',
    views: 2900000
  }
])

useHead({
  title: 'Truyện Yêu Thích - WebTruyện'
})
</script>
