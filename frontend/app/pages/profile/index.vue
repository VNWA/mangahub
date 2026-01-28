<template>
  <div>
    <!-- Main Content -->
    <main class="min-h-screen bg-white dark:bg-slate-900 py-8">
      <div class="container mx-auto px-4 md:px-6 lg:px-8 max-w-6xl">
        <!-- Breadcrumb -->
        <nav class="mb-8 flex items-center gap-2 text-sm">
          <NuxtLink to="/" class="text-slate-600 dark:text-slate-400 hover:text-primary">
            Trang chủ
          </NuxtLink>
          <UIcon name="i-heroicons-chevron-right" class="w-4 h-4 text-slate-400" />
          <span class="text-slate-900 dark:text-white">Hồ sơ</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <!-- Sidebar -->
          <div class="lg:col-span-1">
            <div class="sticky top-4">
              <UserProfileCard :user="currentUser" />
            </div>
          </div>

          <!-- Content -->
          <div class="lg:col-span-3 space-y-8">
            <!-- Tabs -->
            <div class="border-b border-slate-200 dark:border-slate-700">
              <div class="flex gap-6 -mb-px">
                <button @click="switchTab('favorites')" :class="[
                  'py-4 px-1 font-semibold border-b-2 transition-colors',
                  activeTab === 'favorites'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                ]">
                  <div class="flex items-center gap-2">
                    <UIcon name="i-heroicons-heart" class="w-5 h-5" />
                    <span>Yêu thích</span>
                  </div>
                </button>
                <button @click="switchTab('reading')" :class="[
                  'py-4 px-1 font-semibold border-b-2 transition-colors',
                  activeTab === 'reading'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                ]">
                  <div class="flex items-center gap-2">
                    <UIcon name="i-heroicons-document-text" class="w-5 h-5" />
                    <span>Lịch sử đọc</span>
                  </div>
                </button>
                <button @click="switchTab('settings')" :class="[
                  'py-4 px-1 font-semibold border-b-2 transition-colors',
                  activeTab === 'settings'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                ]">
                  <div class="flex items-center gap-2">
                    <UIcon name="i-heroicons-cog-6-tooth" class="w-5 h-5" />
                    <span>Cài đặt</span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Tab Content -->
            <div>
              <div v-if="activeTab === 'favorites'" class="space-y-6">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Truyện yêu thích</h2>
                <UserFavoriteList :favorites="favorites" @remove-favorite="removeFavorite" />
              </div>

              <div v-else-if="activeTab === 'reading'" class="space-y-6">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Lịch sử đọc</h2>
                <UserReadingHistory :reading-history="readingHistory" />
              </div>

              <div v-else class="space-y-6">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Cài đặt tài khoản</h2>
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 space-y-6">
                  <!-- Email Setting -->
                  <div>
                    <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                      Email
                    </label>
                    <UInput :model-value="currentUser.email" type="email" disabled />
                  </div>

                  <!-- Notification Settings -->
                  <div class="space-y-3 border-t border-slate-200 dark:border-slate-700 pt-6">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Thông báo</h3>
                    <label class="flex items-center gap-3 cursor-pointer">
                      <UCheckbox v-model="notificationSettings.emailNewChapter" />
                      <span class="text-sm text-slate-700 dark:text-slate-300">
                        Nhận email khi có chương mới
                      </span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                      <UCheckbox v-model="notificationSettings.emailRecommendations" />
                      <span class="text-sm text-slate-700 dark:text-slate-300">
                        Nhận email về truyện được gợi ý
                      </span>
                    </label>
                  </div>

                  <!-- Privacy Settings -->
                  <div class="space-y-3 border-t border-slate-200 dark:border-slate-700 pt-6">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Quyền riêng tư</h3>
                    <label class="flex items-center gap-3 cursor-pointer">
                      <UCheckbox v-model="privacySettings.publicProfile" />
                      <span class="text-sm text-slate-700 dark:text-slate-300">
                        Cho phép mọi người xem hồ sơ của tôi
                      </span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                      <UCheckbox v-model="privacySettings.showReadingHistory" />
                      <span class="text-sm text-slate-700 dark:text-slate-300">
                        Hiển thị lịch sử đọc công khai
                      </span>
                    </label>
                  </div>

                  <!-- Save Button -->
                  <div class="flex gap-3 border-t border-slate-200 dark:border-slate-700 pt-6">
                    <UButton color="primary" label="Lưu thay đổi" />
                    <UButton color="neutral" variant="ghost" label="Hủy" />
                  </div>
                </div>

                <!-- Danger Zone -->
                <div
                  class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow-md p-6">
                  <h3 class="font-semibold text-red-900 dark:text-red-200 mb-4">Vùng nguy hiểm</h3>
                  <button class="text-red-600 dark:text-red-400 hover:text-red-700 text-sm font-semibold">
                    Xóa tài khoản
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">


const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
auth.fetchUser();

// Get active tab from query params or default to favorites
const activeTab = ref<'favorites' | 'reading' | 'settings'>(
  (route.query.tab as 'favorites' | 'reading' | 'settings') || 'favorites'
)

// Watch for tab changes in query
watch(() => route.query.tab, (newTab) => {
  if (newTab === 'favorites' || newTab === 'reading' || newTab === 'settings') {
    activeTab.value = newTab
  }
})

// User data from auth store
const currentUser = computed(() => ({
  id: auth.user?.id?.toString() || auth.user?.ulid || '1',
  username: auth.user?.name || 'User',
  email: auth.user?.email || '',
  avatar: auth.user?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${encodeURIComponent(auth.user?.email || 'user')}`,
  bio: '📚 Yêu thích truyện tranh và tiểu thuyết',
  favoriteCount: 0, // TODO: Fetch from API
  readingCount: 0, // TODO: Fetch from API
  followersCount: 0, // TODO: Fetch from API
  createdAt: auth.user?.created_at || new Date().toISOString(),
}))

const favorites = ref([
  {
    id: '1',
    title: 'One Piece',
    coverImage: 'https://images.unsplash.com/photo-1621038149384-90c0f91bb3f4?w=400&h=600&fit=crop',
    rating: 9.0,
    status: 'Đang ra',
    author: 'Eiichiro Oda',
  },
  {
    id: '2',
    title: 'Naruto',
    coverImage: 'https://images.unsplash.com/photo-1613395877344-13d4a8e0d049?w=400&h=600&fit=crop',
    rating: 8.9,
    status: 'Hoàn thành',
    author: 'Masashi Kishimoto',
  },
  {
    id: '3',
    title: 'My Hero Academia',
    coverImage: 'https://images.unsplash.com/photo-1623956299424-58e5a4e3f4c7?w=400&h=600&fit=crop',
    rating: 8.7,
    status: 'Đang ra',
    author: 'Kohei Horikoshi',
  },
  {
    id: '4',
    title: 'Attack on Titan',
    coverImage: 'https://images.unsplash.com/photo-1634447288519-eec6d71f7ab3?w=400&h=600&fit=crop',
    rating: 9.1,
    status: 'Hoàn thành',
    author: 'Hajime Isayama',
  },
])

const readingHistory = ref([
  {
    id: '1',
    story: {
      id: '1',
      title: 'One Piece',
      slug: 'one-piece',
      author: 'Eiichiro Oda',
      coverImage: 'https://images.unsplash.com/photo-1621038149384-90c0f91bb3f4?w=400&h=600&fit=crop',
    },
    lastChapterId: '1050',
    lastChapterTitle: 'Chương 1050',
    lastRead: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    progress: 75,
  },
  {
    id: '2',
    story: {
      id: '3',
      title: 'My Hero Academia',
      slug: 'my-hero-academia',
      author: 'Kohei Horikoshi',
      coverImage: 'https://images.unsplash.com/photo-1623956299424-58e5a4e3f4c7?w=400&h=600&fit=crop',
    },
    lastChapterId: '390',
    lastChapterTitle: 'Chương 390',
    lastRead: new Date(Date.now() - 5 * 60 * 60 * 1000).toISOString(),
    progress: 60,
  },
])

const notificationSettings = ref({
  emailNewChapter: true,
  emailRecommendations: false,
})

const privacySettings = ref({
  publicProfile: true,
  showReadingHistory: false,
})

const switchTab = (tab: 'favorites' | 'reading' | 'settings') => {
  activeTab.value = tab
  router.push({ query: { tab } })
}

const removeFavorite = (id: string) => {
  favorites.value = favorites.value.filter(f => f.id !== id)
}

useHead({
  title: 'Hồ sơ - WebTruyện'
})
</script>
