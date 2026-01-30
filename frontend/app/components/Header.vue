<template>
  <header
    class="sticky top-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 shadow-sm">
    <nav class="max-w-7xl mx-auto px-4">
      <!-- Top Bar -->
      <div class="flex items-center justify-between h-14">
        <!-- Logo -->
        <NuxtLink to="/" class="flex items-center gap-2 group flex-shrink-0">
          <div
            class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center group-hover:scale-105 transition-transform">
            <UIcon name="i-heroicons-book-open" class="w-5 h-5 text-white" />
          </div>
          <h1 class="text-xl font-bold text-slate-900 dark:text-white hidden sm:block">WebTruyện</h1>
        </NuxtLink>

        <!-- Desktop Navigation -->
        <div class="hidden md:flex items-center gap-1 mx-2 lg:mx-4">
          <NuxtLink v-for="link in secondaryNav" :key="link.label" :to="link.to"
            class="px-2 lg:px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-slate-800 rounded-md transition-colors whitespace-nowrap"
            active-class="text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-slate-800">
            {{ link.label }}
          </NuxtLink>
        </div>

        <!-- Search Bar with Autocomplete -->
        <div class="hidden lg:flex flex-1 max-w-md mx-2 lg:mx-4 relative">
          <div class="w-full relative">
            <UInput
              v-model="searchQuery"
              icon="i-heroicons-magnifying-glass"
              placeholder="Tìm truyện..."
              @keyup.enter="handleSearch"
              @input="handleSearchInput"
              @focus="showSuggestions = true"
              @blur="handleSearchBlur"
              size="sm"
              class="w-full"
            />
            <!-- Search Suggestions -->
            <div
              v-if="showSuggestions && searchSuggestions.length > 0"
              class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto"
            >
              <NuxtLink
                v-for="suggestion in searchSuggestions"
                :key="suggestion.id"
                :to="`/${suggestion.slug}`"
                class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors border-b border-slate-100 dark:border-slate-700 last:border-b-0"
                @click="showSuggestions = false"
              >
                <img
                  :src="suggestion.coverImage || 'https://via.placeholder.com/60x80'"
                  :alt="suggestion.title"
                  class="w-12 h-16 object-cover rounded"
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-slate-900 dark:text-white truncate">{{ suggestion.title }}</p>
                  <p class="text-xs text-slate-600 dark:text-slate-400 truncate">{{ suggestion.author }}</p>
                </div>
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- Right Actions -->
        <div class="flex items-center gap-1.5">
          <!-- Search Mobile -->
          <UButton variant="ghost" color="neutral" icon="i-heroicons-magnifying-glass" square size="sm"
            class="lg:hidden" @click="showMobileSearch = !showMobileSearch" />

          <!-- Theme Toggle -->
          <ColorModeButton />

          <!-- Notifications (only when logged in) -->
          <NotificationDropdown v-if="isLoggedIn" />

          <!-- User Menu / Auth -->
          <template v-if="isLoggedIn">
            <UDropdownMenu :items="userMenuItems" :popper="{ placement: 'bottom-end' }">
              <UButton color="neutral" variant="ghost" square size="sm"
                class="p-0 hover:ring-2 hover:ring-purple-500 dark:hover:ring-purple-400 transition-all">
                <UAvatar :src="userAvatar" :alt="auth.user?.name || 'User'" size="sm"
                  class="ring-2 ring-purple-500/20 dark:ring-purple-400/20" />
              </UButton>
              <template #header>
                <div class="px-3 py-2 border-b border-slate-200 dark:border-slate-700">
                  <p class="text-sm font-semibold text-slate-900 dark:text-white truncate max-w-[200px]">
                    {{ auth.user?.name || 'User' }}
                  </p>
                  <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[200px]">
                    {{ auth.user?.email }}
                  </p>
                </div>
              </template>
            </UDropdownMenu>
          </template>
          <template v-else>
            <div class="hidden sm:flex items-center gap-2">
              <UButton to="/auth/login" variant="ghost" color="neutral" size="sm" label="Đăng nhập" />
              <UButton to="/auth/register" color="primary" size="sm" label="Đăng ký" />
            </div>
            <UButton to="/auth/login" variant="ghost" color="neutral" icon="i-heroicons-user" square size="sm"
              class="sm:hidden" />
          </template>
        </div>
      </div>

      <!-- Mobile Search Bar -->
      <div v-if="showMobileSearch" class="pb-3 lg:hidden relative">
        <UInput
          v-model="searchQuery"
          icon="i-heroicons-magnifying-glass"
          placeholder="Tìm truyện..."
          @keyup.enter="handleSearch"
          @input="handleSearchInput"
          @focus="showSuggestions = true"
          @blur="handleSearchBlur"
          size="sm"
        />
        <!-- Mobile Search Suggestions -->
        <div
          v-if="showSuggestions && searchSuggestions.length > 0"
          class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto"
        >
          <NuxtLink
            v-for="suggestion in searchSuggestions"
            :key="suggestion.id"
            :to="`/${suggestion.slug}`"
            class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors border-b border-slate-100 dark:border-slate-700 last:border-b-0"
            @click="showSuggestions = false; showMobileSearch = false"
          >
            <img
              :src="suggestion.coverImage || 'https://via.placeholder.com/60x80'"
              :alt="suggestion.title"
              class="w-12 h-16 object-cover rounded"
            />
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-900 dark:text-white truncate">{{ suggestion.title }}</p>
              <p class="text-xs text-slate-600 dark:text-slate-400 truncate">{{ suggestion.author }}</p>
            </div>
          </NuxtLink>
        </div>
      </div>
    </nav>
  </header>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const router = useRouter()
const searchQuery = ref('')
const showMobileSearch = ref(false)
const showSuggestions = ref(false)
const searchSuggestions = ref<any[]>([])
let searchTimeout: NodeJS.Timeout | null = null

const isLoggedIn = computed(() => !!auth.logged && !!auth.user?.email)

const secondaryNav = [
  { label: 'Trang chủ', to: '/' },
  { label: 'BXH', to: '/ranking' },
  { label: 'Thể loại', to: '/category/list' },
  { label: 'Hot', to: '/trending' }
]

const userMenuItems = computed(() => [
  [
    { label: 'Trang cá nhân', icon: 'i-heroicons-user', to: '/profile' },
    { label: 'Truyện yêu thích', icon: 'i-heroicons-heart', to: '/favorites' },
    { label: 'Lịch sử đọc', icon: 'i-heroicons-clock', to: '/profile?tab=reading' }
  ],
  [
    { label: 'Cài đặt', icon: 'i-heroicons-cog-6-tooth', to: '/profile?tab=settings' },
    {
      label: 'Đăng xuất',
      icon: 'i-heroicons-arrow-right-on-rectangle',
      onClick: async () => {
        console.log('logout')
        await auth.logout()
      }
    }
  ]
])

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    navigateTo(`/search?q=${encodeURIComponent(searchQuery.value)}`)
    showMobileSearch.value = false
    showSuggestions.value = false
  }
}

const handleSearchInput = async () => {
  // Clear previous timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  const query = searchQuery.value.trim()
  if (!query || query.length < 2) {
    searchSuggestions.value = []
    showSuggestions.value = false
    return
  }

  // Debounce search
  searchTimeout = setTimeout(async () => {
    try {
      const data = await $http<{
        ok: boolean
        data: Array<{
          id: number
          title: string
          slug: string
          author: string
          coverImage: string | null
        }>
      }>('/api/v1/search', {
        query: {
          q: query,
          per_page: 5,
        },
      })

      if (data?.ok && data.data) {
        searchSuggestions.value = data.data.map((item: any) => ({
          id: item.id,
          title: item.title || item.name || '',
          slug: item.slug || '',
          author: item.author?.name || (typeof item.author === 'string' ? item.author : 'Unknown'),
          coverImage: item.coverImage || item.avatar || '',
        }))
        showSuggestions.value = true
      }
    } catch (error) {
      console.error('Search error:', error)
      searchSuggestions.value = []
    }
  }, 300)
}

const handleSearchBlur = () => {
  // Delay to allow click on suggestion
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

// Get user avatar URL
const userAvatar = computed(() => {
  if (auth.user?.avatar) {
    return auth.user.avatar
  }
  // Fallback to generated avatar based on user name or email
  const seed = auth.user?.name || auth.user?.email || 'user'
  return `https://api.dicebear.com/7.x/avataaars/svg?seed=${encodeURIComponent(seed)}`
})
</script>
