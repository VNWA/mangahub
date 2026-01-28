<template>
  <div>
    <div class="max-w-7xl mx-auto px-4 py-8 md:py-12 space-y-12 md:space-y-16">
      <section>
        <div class="mb-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white mb-2">🏆 Top Truyện</h2>
              <p class="text-slate-600 dark:text-slate-400">Bảng xếp hạng truyện được yêu thích nhất</p>
            </div>
            <NuxtLink to="/ranking"
              class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-semibold text-sm flex items-center gap-1">
              Xem tất cả
              <UIcon name="i-heroicons-arrow-right" class="w-4 h-4" />
            </NuxtLink>
          </div>

          <!-- Tabs -->
          <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700 overflow-x-auto mb-6">
            <button v-for="(tab, index) in topTabs" :key="tab.value" @click="activeTopTab = index" :class="[
              'px-4 py-2 font-semibold text-sm border-b-2 transition-all whitespace-nowrap flex items-center gap-2',
              activeTopTab === index
                ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400'
                : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
            ]">
              <UIcon :name="tab.icon" class="w-4 h-4" />
              <span>{{ tab.label }}</span>
            </button>
          </div>
        </div>

        <!-- Top Stories Grid -->
        <div v-if="topPending" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <div v-for="i in 10" :key="i" class="animate-pulse">
            <div class="aspect-[3/4] bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
          </div>
        </div>
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
          <StoryCard v-for="(story, index) in currentTopStories" :key="story.id" :story="story" variant="grid"
            :rank="index + 1" />
        </div>
      </section>

      <!-- Featured -->
      <section v-if="featuredStories.length > 0">
        <div class="mb-6">
          <h2 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white mb-2">📌 Truyện Nổi Bật</h2>
          <p class="text-slate-600 dark:text-slate-400">Những bộ truyện được yêu thích nhất</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
          <StoryCard v-for="story in featuredStories.slice(0, 3)" :key="story.id" :story="story" variant="grid" />
        </div>
      </section>

      <!-- New Updates -->
      <section>
        <StoryGrid :stories="newStories" title="✨ Truyện Mới Cập Nhật" subtitle="Cập nhật hàng ngày"
          view-all-link="/category/all" :columns="5" />
      </section>

      <!-- Hot Stories -->
      <section>
        <StoryGrid :stories="hotStories" title="🔥 Truyện Hot" subtitle="Đang được đọc nhiều" view-all-link="/ranking"
          :columns="5" />
      </section>

      <!-- Full Stories -->
      <section>
        <StoryGrid :stories="fullStories" title="✅ Truyện Đã Hoàn Thành" subtitle="Ngồi chill không lo thiếu truyện"
          view-all-link="/search" :columns="5" />
      </section>

      <!-- Recommended -->
      <section>
        <StoryGrid :stories="recommendedStories" title="💎 Gợi Ý Cho Bạn" subtitle="Dựa trên sở thích của bạn"
          :columns="5" />
      </section>
    </div>


  </div>
</template>

<script setup lang="ts">
interface Story {
  id: number | string
  name: string
  slug: string
  avatar?: string | null
  description?: string
  status?: string
  views?: number
  rating?: number
  follows?: number
  author?: {
    id: number
    name: string
  } | null
  badge?: {
    id: number
    name: string
  } | null
  categories?: Array<{
    id: number
    name: string
    slug: string
  }>
  created_at?: string
  updated_at?: string
}

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
const { data: topData, pending: topPending, refresh: refreshTop } = useLazyHttp<{ ok: boolean; data: Story[] }>('/mangas/top', {
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
const { data: featuredData, pending: featuredPending } = useLazyHttp<{ ok: boolean; data: Story[] }>('/mangas/featured', {
  query: { limit: 3 }
})

const featuredStories = computed(() => featuredData.value?.data || [])

// Fetch new mangas
const { data: newData, pending: newPending } = useLazyHttp<{ ok: boolean; data: Story[] }>('/mangas/new', {
  query: { limit: 20 }
})

const newStories = computed(() => newData.value?.data || [])

// Fetch hot mangas
const { data: hotData, pending: hotPending } = useLazyHttp<{ ok: boolean; data: Story[] }>('/mangas/hot', {
  query: { limit: 20 }
})

const hotStories = computed(() => hotData.value?.data || [])

// Fetch completed mangas
const { data: completedData, pending: completedPending } = useLazyHttp<{ ok: boolean; data: Story[] }>('/mangas/completed', {
  query: { limit: 20 }
})

const fullStories = computed(() => completedData.value?.data || [])

// Recommended (use hot for now)
const recommendedStories = computed(() => hotStories.value.slice(0, 10))

useHead({
  title: 'WebTruyện - Đọc Truyện Online Miễn Phí',
  meta: [
    { name: 'description', content: 'WebTruyện - Đọc hàng ngàn bộ truyện online miễn phí, cập nhật hàng ngày' }
  ]
})
</script>
