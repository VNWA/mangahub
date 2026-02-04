<template>
  <div>
    <UContainer class=" space-y-12 md:space-y-16">
      <!-- Top Ranking Section -->
      <section>
        <div class="mb-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white mb-2">🏆 Top Truyện</h2>
              <p class="text-zinc-600 dark:text-zinc-400">Bảng xếp hạng truyện được yêu thích nhất</p>
            </div>
            <NuxtLink to="/ranking"
              class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-semibold text-sm flex items-center gap-1">
              Xem tất cả
              <UIcon name="i-heroicons-arrow-right" class="w-4 h-4" />
            </NuxtLink>
          </div>

          <!-- Tabs -->
          <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700 overflow-x-auto mb-6">
            <button v-for="(tab, index) in topTabs" :key="tab.value" @click="activeTopTab = index" :class="[
              'px-4 py-2 font-semibold text-sm border-b-2 transition-all whitespace-nowrap flex items-center gap-2',
              activeTopTab === index
                ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400'
                : 'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
            ]">
              <UIcon :name="tab.icon" class="w-4 h-4" />
              <span>{{ tab.label }}</span>
            </button>
          </div>
        </div>

        <!-- Top Stories Grid -->
        <div v-if="topPending" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <div v-for="i in 10" :key="i" class="animate-pulse">
            <div class="aspect-[3/4] bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
          </div>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <div v-for="(item, index) in currentTopStories" :key="index">
            <MangaCard :manga="item" />
          </div>
        </div>
      </section>

      <!-- Featured Section -->
      <section v-if="featuredStories.length > 0">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white mb-2">⭐ Truyện Nổi Bật</h2>
            <p class="text-zinc-600 dark:text-zinc-400">Những bộ truyện được đề xuất đặc biệt</p>
          </div>
          <NuxtLink to="/trending"
            class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-semibold text-sm flex items-center gap-1">
            Xem tất cả
            <UIcon name="i-heroicons-arrow-right" class="w-4 h-4" />
          </NuxtLink>
        </div>

        <div v-if="featuredPending" class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
          <div v-for="i in 3" :key="i" class="animate-pulse">
            <div class="aspect-[3/4] bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
          </div>
        </div>
        <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
          <div v-for="(item, index) in featuredStories" :key="index">
            <MangaCard :manga="item" variant="horizontal" />
          </div>
        </div>
      </section>

      <!-- New Manga Section -->
      <section>
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white mb-2">🆕 Truyện Mới</h2>
            <p class="text-zinc-600 dark:text-zinc-400">Những bộ truyện mới được cập nhật gần đây</p>
          </div>
          <NuxtLink to="/search?sort_by=Mới nhất"
            class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-semibold text-sm flex items-center gap-1">
            Xem tất cả
            <UIcon name="i-heroicons-arrow-right" class="w-4 h-4" />
          </NuxtLink>
        </div>

        <div v-if="newPending" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <div v-for="i in 10" :key="i" class="animate-pulse">
            <div class="aspect-[3/4] bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
          </div>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <div v-for="(item, index) in newStories" :key="index">
            <MangaCard :manga="item" />
          </div>
        </div>
      </section>

      <!-- Hot Manga Section -->
      <section>
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white mb-2">🔥 Truyện Hot</h2>
            <p class="text-zinc-600 dark:text-zinc-400">Những bộ truyện đang được quan tâm nhiều nhất</p>
          </div>
          <NuxtLink to="/trending"
            class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-semibold text-sm flex items-center gap-1">
            Xem tất cả
            <UIcon name="i-heroicons-arrow-right" class="w-4 h-4" />
          </NuxtLink>
        </div>

        <div v-if="hotPending" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <div v-for="i in 10" :key="i" class="animate-pulse">
            <div class="aspect-[3/4] bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
          </div>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <div v-for="(item, index) in hotStories" :key="index">
            <MangaCard :manga="item" />
          </div>
        </div>
      </section>

      <!-- Completed Manga Section -->
      <section>
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white mb-2">✅ Truyện Hoàn Thành</h2>
            <p class="text-zinc-600 dark:text-zinc-400">Những bộ truyện đã hoàn thành, đọc ngay không cần chờ</p>
          </div>
          <NuxtLink to="/search?status=completed"
            class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-semibold text-sm flex items-center gap-1">
            Xem tất cả
            <UIcon name="i-heroicons-arrow-right" class="w-4 h-4" />
          </NuxtLink>
        </div>

        <div v-if="completedPending"
          class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <div v-for="i in 10" :key="i" class="animate-pulse">
            <div class="aspect-[3/4] bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
          </div>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <div v-for="(item, index) in fullStories" :key="index">
            <MangaCard :manga="item" />
          </div>
        </div>
      </section>

      <!-- Categories Section -->
      <section v-if="categories.length > 0">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white mb-2">📚 Thể Loại</h2>
            <p class="text-zinc-600 dark:text-zinc-400">Khám phá truyện theo thể loại yêu thích</p>
          </div>
          <NuxtLink to="/category/list"
            class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-semibold text-sm flex items-center gap-1">
            Xem tất cả
            <UIcon name="i-heroicons-arrow-right" class="w-4 h-4" />
          </NuxtLink>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <NuxtLink v-for="category in categories.slice(0, 12)" :key="category.id" :to="`/category/${category.slug}`"
            class="group relative bg-white dark:bg-zinc-800 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-all p-4 text-center">
            <div
              class="h-16 bg-gradient-to-br from-purple-500 to-blue-500 group-hover:from-purple-600 group-hover:to-blue-600 transition-colors rounded-lg flex items-center justify-center mb-3">
              <UIcon name="i-heroicons-book-open" class="w-8 h-8 text-white" />
            </div>
            <h3
              class="font-semibold text-zinc-900 dark:text-white text-sm group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
              {{ category.name }}
            </h3>
          </NuxtLink>
        </div>
      </section>
    </UContainer>
  </div>
</template>

<script setup lang="ts">
import type { Manga } from '~/types'
import type { Category } from '~/types/category'

// Top Ranking Tabs
const activeTopTab = ref(0)
const topTabs = [
  { label: 'Top Tuần', icon: 'i-heroicons-calendar-days', value: 'weekly' },
  { label: 'Top Tháng', icon: 'i-heroicons-calendar', value: 'monthly' },
  { label: 'Top Rating', icon: 'i-heroicons-star', value: 'rating' },
  { label: 'Top All', icon: 'i-heroicons-trophy', value: 'all' }
]

// Fetch top mangas - reactive based on active tab
const topType = computed(() => topTabs[activeTopTab.value]?.value || 'all')
const { data: topData, pending: topPending, refresh: refreshTop } = useLazyHttp<{ ok: boolean; data: Manga[] }>('/mangas/top', {
  query: computed(() => ({
    type: topType.value,
    limit: 10
  }))
})

watch(activeTopTab, () => {
  refreshTop()
})

const currentTopStories = computed(() => topData.value?.data || [])

// Fetch featured mangas
const { data: featuredData, pending: featuredPending } = useLazyHttp<{ ok: boolean; data: Manga[] }>('/mangas/featured', {
  query: { limit: 5 }
})

const featuredStories = computed(() => featuredData.value?.data || [])

// Carousel items from featured mangas
const carouselItems = computed(() => {
  return featuredStories.value.map((manga) => ({
    id: String(manga.id),
    slug: manga.slug,
    title: manga.name || '',
    author: typeof manga.author === 'object' && manga.author ? manga.author.name : manga.author || 'Tác giả',
    avatar: manga.avatar || 'https://via.placeholder.com/800x400',
    description: manga.description || 'Không có mô tả'
  }))
})

// Fetch new mangas
const { data: newData, pending: newPending } = useLazyHttp<{ ok: boolean; data: Manga[] }>('/mangas/new', {
  query: { limit: 20 }
})

const newStories = computed(() => newData.value?.data || [])

// Fetch hot mangas
const { data: hotData, pending: hotPending } = useLazyHttp<{ ok: boolean; data: Manga[] }>('/mangas/hot', {
  query: { limit: 20 }
})

const hotStories = computed(() => hotData.value?.data || [])

// Fetch completed mangas
const { data: completedData, pending: completedPending } = useLazyHttp<{ ok: boolean; data: Manga[] }>('/mangas/completed', {
  query: { limit: 20 }
})

const fullStories = computed(() => completedData.value?.data || [])

// Fetch categories
const { data: categoriesData } = useLazyHttp<{
  ok: boolean
  data: Category[]
}>('/categories')

const categories = computed(() => categoriesData.value?.data || [])

useHead({
  title: 'WebTruyện - Đọc Truyện Online Miễn Phí',
  meta: [
    { name: 'description', content: 'WebTruyện - Đọc hàng ngàn bộ truyện online miễn phí, cập nhật hàng ngày' }
  ]
})
</script>
