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
              <UserProfileCard />
            </div>
          </div>

          <!-- Content -->
          <div class="lg:col-span-3 space-y-8">
            <UNavigationMenu :items="links" highlight class="-mx-1 flex-1" />

            <!-- Tab Content -->
            <div>

              <NuxtPage />


            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const links = [[{
  label: 'Cài đặt',
  icon: 'i-lucide-user',
  to: '/profile',
  exact: true
}, {
  label: 'Coin',
  icon: 'i-heroicons-currency-dollar',
  to: '/profile/coin'
}, {
  label: 'Lịch sử đọc',
  icon: 'i-lucide-history',
  to: '/profile/histories'
}, {
  label: 'Truyện yêu thích',
  icon: 'i-lucide-heart',
  to: '/profile/favorites'
}]] satisfies NavigationMenuItem[][]

useHead({
  title: 'Hồ sơ - WebTruyện'
})
</script>
