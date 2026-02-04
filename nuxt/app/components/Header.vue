<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'
import { ModalSearch } from '#components'
const auth = useAuthStore()
const route = useRoute()
const overlay = useOverlay()
const openSearch = () => {
  const modal = overlay.create(ModalSearch)
  modal.open({
    onClose: () => {
      modal.close()
    }
  })
}

const items = computed<NavigationMenuItem[]>(() => [
  {
    label: 'Trang chủ',
    to: '/',
    active: route.path === '/'
  },
  {
    label: 'Truyện',
    to: '/mangas',
    active: route.path === '/mangas'
  },
  {
    label: 'BXH',
    to: '/ranking',
    active: route.path === '/ranking'
  },
  {
    label: 'Truyện hot',
    to: '/trending',
    active: route.path === '/trending'
  },
  {
    label: 'Thể loại',
    to: '/category/list',
    active: route.path === '/category/list'
  }
])
const userMenuItems = computed(() => [
  [
    {
      label: auth.user?.name || 'User',
      avatar: {
        src: auth.user?.avatar || ''
      },
      type: 'label'
    }
  ],
  [
    { label: 'Trang cá nhân', icon: 'i-heroicons-user', to: '/profile' },
    { label: 'Truyện yêu thích', icon: 'i-heroicons-heart', to: '/favorites' },
    { label: 'Lịch sử đọc', icon: 'i-heroicons-clock', to: '/profile?tab=reading' },
    { label: 'Quản lý Coin', icon: 'i-heroicons-currency-dollar', to: '/coins' }
  ],
  [
    { label: 'Cài đặt', icon: 'i-heroicons-cog-6-tooth', to: '/profile?tab=settings' },
    {
      label: 'Đăng xuất',
      icon: 'i-heroicons-arrow-right-on-rectangle',
      onClick: async () => {
        await auth.logout()
      }
    }
  ]
])
</script>

<template>
  <UHeader :ui="{ root: 'relative' }">
    <template #title>
      <div class="flex items-center gap-2 group flex-shrink-0 w-full">
        <div
          class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center group-hover:scale-105 transition-transform">
          <UIcon name="i-heroicons-book-open" class="w-5 h-5 text-white" />
        </div>
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white hidden sm:block">WebTruyện</h1>
      </div>

    </template>

    <UNavigationMenu :items="items" />

    <template #right>
      <UColorModeButton />
      <UButton variant="ghost" color="neutral" square size="sm" class="relative" @click="openSearch">

        <UIcon name="solar:magnifer-linear" class="w-5 h-5" />
      </UButton>
      <NotificationDropdown v-if="auth.logged" />

      <template v-if="auth.logged">
        <UDropdownMenu :items="userMenuItems" :popper="{ placement: 'bottom-end' }">
          <UButton color="neutral" variant="ghost" square size="sm"
            class="p-0 hover:ring-2 hover:ring-purple-500 dark:hover:ring-purple-400 transition-all">
            <UAvatar :src="auth.user.avatar || ''" :alt="auth.user?.name || 'User'" size="sm"
              class="ring-2 ring-purple-500/20 dark:ring-purple-400/20" />
          </UButton>

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
    </template>
  </UHeader>
</template>
