<template>
  <div>
    <!-- <div
      class="sticky top-0 z-40 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-700 shadow-sm">
      <UContainer class="py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
       
    <UButton color="neutral" variant="ghost" icon="i-heroicons-bars-3" square size="sm" class="md:hidden"
      @click="showSidebar = true" />
    <NuxtLink to="/" class="flex items-center gap-2 hover:text-primary">
      <UIcon name="i-heroicons-arrow-left" class="w-5 h-5" />
      <span class="text-sm font-semibold hidden sm:inline">Trang chủ</span>
    </NuxtLink>
  </div>

  <div class="flex items-center gap-2">
    <USelect v-if="availableServers.length > 1" v-model="selectedServerId" :options="availableServers"
      value-attribute="id" option-attribute="name" size="sm" class="min-w-[150px] hidden sm:block"
      @change="switchServer" />
    <UButton v-if="chapterData?.data?.id" color="neutral" variant="ghost" icon="i-heroicons-flag" square size="sm"
      @click="showReportModal()" />
    <UButton color="neutral" variant="ghost" icon="i-heroicons-arrows-pointing-out" square size="sm"
      @click="toggleFullscreen" />
  </div>
  </UContainer>
  </div> -->


    <div class="min-h-screen">
      <UContainer class="py-8 md:py-12 max-w-5xl">
        <ChapterHeader :chapter="chapter" :manga-slug="mangaSlug" />

        <div v-if="chapterData?.data?.is_locked && !chapterData?.data?.is_accessible"
          class="mb-8 p-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
          <div class="flex items-start gap-4">
            <UIcon name="i-heroicons-lock-closed"
              class="w-6 h-6 text-yellow-600 dark:text-yellow-400 shrink-0 mt-0.5" />
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-200 mb-2">Chapter này đã bị khóa</h3>
              <p class="text-sm text-yellow-800 dark:text-yellow-300 mb-4">
                Bạn cần {{ formatCoin(chapterData.data.coin_cost) }} coin để mở khóa chapter này.
              </p>
              <UButton v-if="auth.logged" @click="handleUnlockChapter" :loading="unlocking"
                :disabled="userCoin < (chapterData.data.coin_cost || 0)" color="warning" size="sm">
                <UIcon name="i-heroicons-currency-dollar" class="w-4 h-4" />
                Mở khóa với {{ formatCoin(chapterData.data.coin_cost || 0) }} coin
              </UButton>
              <div v-else class="flex items-center gap-2">
                <UButton to="/auth/login" color="primary" size="sm">
                  Đăng nhập để mở khóa
                </UButton>
              </div>
            </div>
          </div>
        </div>

        <article v-if="chapterData?.data?.is_accessible" class="max-w-none mb-12">
          <div v-if="chapterData?.data?.content?.images && chapterData.data.content.images.length > 0"
            class="space-y-4">
            <!-- <NuxtImg v-for="(image, index) in chapterData.data.content.images" :key="image" :src="image" width="500"
              :alt="`Page ${index + 1}`" loading="lazy" decoding="async" sizes="100vw"
              class="w-full h-auto rounded-lg shadow-sm" /> -->


            <div v-for="(item, index) in chapterData.data.content.images" :key="index">
              <img :src="item" :alt="`Page ${index + 1}`" :width="500" :height="500"
                :loading="index > 8 ? 'lazy' : 'eager'" class="rounded-md size-full object-cover">
            </div>
          </div>

        </article>

        <ChapterNavigation :previous-chapter="previousChapter" :next-chapter="nextChapter" :manga-slug="mangaSlug" />

        <div class="pt-8 border-t border-zinc-200 dark:border-zinc-700">
          <h2 class="text-2xl font-bold mb-6">Bình luận chương</h2>
          <CommentSection v-if="chapterData?.data?.id" :commentable-type="'MangaChapter'"
            :commentable-id="chapterData.data.id" />
        </div>
      </UContainer>
    </div>

    <div
      class="sticky bottom-0 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-t border-zinc-200 dark:border-zinc-700 shadow-lg">
      <UContainer class="lg:py-3 py-1 flex items-center justify-between gap-2">
        <UButton :disabled="!previousChapter" :to="`/${mangaSlug}/${previousChapter ? previousChapter.id : ''}`"
          color="neutral" variant="outline" icon="i-heroicons-arrow-left" class="shrink-0 disabled:opacity-50">
          <span class="hidden sm:inline">Chương trước</span>
        </UButton>
        <USlideover side="right" :title="mangaData?.data?.name">
          <UButton color="neutral" variant="outline" :label="chapterData?.data?.name" icon="i-heroicons-document-text"
            trailing-icon="i-heroicons-chevron-down" />
          <template #body>
            <div class="space-y-4">
              <h2 class="text-2xl font-bold mb-6">Danh sách chương</h2>
              <div v-for="chapter in allChapters.reverse()" :key="chapter.id">
                <NuxtLink :to="`/${mangaSlug}/${chapter.slug}`"
                  class="block p-4 bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors border border-zinc-200 dark:border-zinc-700">
                  {{ chapter.name }}
                </NuxtLink>
              </div>
            </div>
          </template>
        </USlideover>


        <UButton :disabled="!nextChapter" :to="`/${mangaSlug}/${nextChapter ? nextChapter.id : ''}`" color="neutral"
          variant="outline" trailing-icon="i-heroicons-arrow-right" class="shrink-0 disabled:opacity-50">
          <span class="hidden sm:inline">Chương tiếp</span>
        </UButton>

      </UContainer>
    </div>


  </div>
</template>

<script setup lang="ts">
import { ModalReportChapter } from '#components'

const overlay = useOverlay()
const showSidebar = ref(true)

const route = useRoute()
const mangaSlug = route.params.slug as string
const chapterSlug = route.params.chapter as string
const { addHistory } = useReadingHistory()
const auth = useAuthStore()
const toast = useToast()
const unlocking = ref(false)

const userCoin = computed(() => auth.user?.coin ?? 0)

const formatCoin = (coin: number) => {
  return new Intl.NumberFormat('vi-VN').format(coin)
}
const showReportModal = () => {
  const modal = overlay.create(ModalReportChapter)
  modal.open({
    manga_slug: mangaSlug,
    chapter_slug: chapterSlug,
    onClose: () => {
      modal.close()
    }
  })
}
// Server selection
const selectedServerId = ref<number | null>(null)
// Load chapter data
const chapterUrl = computed(() => {
  const params = new URLSearchParams()
  if (selectedServerId.value) {
    params.set('server_id', selectedServerId.value.toString())
  }
  const queryString = params.toString()
  return `/mangas/${mangaSlug}/chapters/${chapterSlug}${queryString ? `?${queryString}` : ''}`
})

const { data: chapterData, pending: chapterPending, refresh: refreshChapter } = useLazyHttp<{
  ok: boolean
  data: {
    id: number
    name: string
    slug: string
    order: number
    description: string | null
    coin_cost: number
    is_locked: boolean
    is_unlocked: boolean
    is_accessible: boolean
    manga: {
      id: number
      name: string
      slug: string
    }
    prev_chapter: {
      id: number
      name: string
      slug: string
      order: number
    } | null
    next_chapter: {
      id: number
      name: string
      slug: string
      order: number
    } | null
    content: {
      server_id: number
      server_name: string | null
      images: string[]
      content: string | null
    } | null
    available_servers: Array<{
      id: number
      name: string
    }>
    created_at: string
    updated_at: string
  }
}>(chapterUrl.value)

// Load manga to get all chapters
const { data: mangaData } = useLazyHttp<{
  ok: boolean
  data: {
    id: number
    name: string
    slug: string
    chapters: Array<{
      id: number
      name: string
      slug: string
      order: number
    }>
  }
}>(`/mangas/${mangaSlug}`)

const allChapters = computed(() => {
  return mangaData.value?.data?.chapters || []
})

const availableServers = computed(() => {
  return chapterData.value?.data?.available_servers || []
})

// Set default server when chapter loads
watch(chapterData, (data) => {
  if (data?.data?.available_servers && data.data.available_servers.length > 0) {
    if (!selectedServerId.value || !data.data.available_servers.find((s: any) => s.id === selectedServerId.value)) {
      selectedServerId.value = data.data.content?.server_id || data.data.available_servers[0]?.id || null
    }
  }
}, { immediate: true })

const switchServer = () => {
  refreshChapter()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}


const chapter = computed(() => {
  const data = chapterData.value?.data
  if (!data) return null
  return {
    id: data.id,
    number: data.order,
    title: data.name,
    uploadedAt: data.created_at,
    views: 0, // Will be updated from manga views
  }
})

const previousChapter = computed(() => {
  const data = chapterData.value?.data
  if (!data?.prev_chapter) return null
  return {
    id: data.prev_chapter.slug,
    number: data.prev_chapter.order,
    title: data.prev_chapter.name,
  }
})

const nextChapter = computed(() => {
  const data = chapterData.value?.data
  if (!data?.next_chapter) return null
  return {
    id: data.next_chapter.slug,
    number: data.next_chapter.order,
    title: data.next_chapter.name,
  }
})

const selectedChapter = ref(chapterSlug)

// Save reading history
watch(chapterData, async (data) => {
  if (data?.data && data.data.manga) {
    await addHistory({
      manga_id: data.data.manga.id,
      chapter_id: data.data.id,
      chapter_order: data.data.order,
      chapter_name: data.data.name,
    })
  }
}, { immediate: true })


const toggleFullscreen = () => {
  document.documentElement.requestFullscreen?.()
}

const goToChapter = () => {
  navigateTo(`/${mangaSlug}/${selectedChapter.value}`)
}

const handleUnlockChapter = async () => {
  if (!chapterData.value?.data?.id) return

  unlocking.value = true
  try {
    const data = await $http<{
      ok: boolean
      message: string
      data: {
        unlock: {
          id: number
          chapter_id: number
          coin_spent: number
        }
        balance: number
      }
    }>('/coins/unlock-chapter', {
      method: 'POST',
      body: {
        chapter_id: chapterData.value.data.id
      }
    })

    if (data?.ok) {
      toast.add({
        title: 'Thành công',
        description: data.message || 'Đã mở khóa chapter thành công',
        color: 'success'
      })

      // Update user coin
      if (auth.user) {
        auth.user.coin = data.data.balance
      }

      // Refresh chapter data
      await refreshChapter()
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || 'Không thể mở khóa chapter',
      color: 'error'
    })
  } finally {
    unlocking.value = false
  }
}

watch(() => chapterSlug, () => {
  selectedChapter.value = chapterSlug
  if (chapterData.value?.data) {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
})

useHead({
  title: computed(() => `${chapter.value?.title || 'Loading...'} - ${mangaData.value?.data?.name || 'Manga'}`)
})
</script>
