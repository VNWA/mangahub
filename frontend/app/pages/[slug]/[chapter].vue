<template>
  <div class="bg-white dark:bg-slate-900">
    <!-- Reading Controls Header -->
    <div class="sticky top-0 z-40 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm">
      <div class="container mx-auto px-4 py-3 flex items-center justify-between">
        <NuxtLink to="/" class="flex items-center gap-2 hover:text-primary">
          <UIcon name="i-heroicons-arrow-left" class="w-5 h-5" />
          <span class="text-sm font-semibold hidden sm:inline">Trang chủ</span>
        </NuxtLink>

        <div class="flex items-center gap-2">
          <!-- Font Size -->
          <UDropdownMenu :items="fontSizeOptions" :popper="{ placement: 'bottom' }">
            <UButton
              color="neutral"
              variant="ghost"
              icon="i-heroicons-document-text"
              label="Cỡ chữ"
              trailing-icon="i-heroicons-chevron-down"
              size="sm"
            />
          </UDropdownMenu>

          <!-- Background Color -->
          <UDropdownMenu :items="bgColorOptions" :popper="{ placement: 'bottom' }">
            <UButton
              color="neutral"
              variant="ghost"
              icon="i-heroicons-swatch"
              label="Nền"
              trailing-icon="i-heroicons-chevron-down"
              size="sm"
            />
          </UDropdownMenu>

          <!-- Settings -->
          <UButton
            color="neutral"
            variant="ghost"
            icon="i-heroicons-cog-6-tooth"
            square
            size="sm"
          />

          <!-- Fullscreen -->
          <UButton
            color="neutral"
            variant="ghost"
            icon="i-heroicons-arrow-up-right"
            square
            size="sm"
            @click="toggleFullscreen"
          />
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <main :style="{ fontSize: `${fontSize}px`, backgroundColor: bgColor }" class="min-h-screen transition-all">
      <div class="container mx-auto px-4 py-12 max-w-4xl">
        <!-- Chapter Header -->
        <div v-if="chapter" class="mb-8 pb-8 border-b border-slate-200 dark:border-slate-700">
          <h1 class="text-3xl md:text-4xl font-bold mb-2">
            {{ chapter.title }}
          </h1>
          <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400">
            <span>{{ formatDate(chapter.uploadedAt) }}</span>
            <NuxtLink
              :to="`/${storySlug}`"
              class="text-primary hover:underline"
            >
              ← Về trang truyện
            </NuxtLink>
          </div>
        </div>

        <!-- Chapter Content -->
        <article :style="{ lineHeight: `${lineHeight}` }" class="prose dark:prose-invert max-w-none mb-12">
          <div v-if="chapterData?.content?.images" class="space-y-4">
            <img
              v-for="(image, index) in chapterData.content.images"
              :key="index"
              :src="image"
              :alt="`Page ${index + 1}`"
              class="w-full h-auto rounded-lg shadow-sm"
              loading="lazy"
            />
          </div>
          <div v-else-if="chapterData?.content?.content" class="space-y-6">
            <div v-html="chapterData.content.content" class="text-justify"></div>
          </div>
          <div v-else class="text-center py-12">
            <p class="text-slate-500 dark:text-slate-400">Chưa có nội dung</p>
          </div>
        </article>

        <!-- Chapter Navigation -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-12">
          <NuxtLink
            v-if="previousChapter"
            :to="`/${storySlug}/${previousChapter.id}`"
            class="flex items-center justify-between p-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
          >
            <div class="flex items-center gap-2">
              <UIcon name="i-heroicons-arrow-left" class="w-5 h-5" />
              <div>
                <p class="text-xs text-slate-600 dark:text-slate-400">Chương trước</p>
                <p class="font-semibold">{{ previousChapter.title }}</p>
              </div>
            </div>
          </NuxtLink>
          <div v-else></div>

          <NuxtLink
            v-if="nextChapter"
            :to="`/${storySlug}/${nextChapter.id}`"
            class="flex items-center justify-between p-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors text-right"
          >
            <div class="flex items-center justify-end gap-2 flex-1">
              <div>
                <p class="text-xs text-slate-600 dark:text-slate-400">Chương tiếp</p>
                <p class="font-semibold">{{ nextChapter.title }}</p>
              </div>
              <UIcon name="i-heroicons-arrow-right" class="w-5 h-5" />
            </div>
          </NuxtLink>
        </div>

        <!-- Comments Section -->
        <div class="pt-8 border-t border-slate-200 dark:border-slate-700">
          <h2 class="text-2xl font-bold mb-6">Bình luận chương</h2>
          <CommentSection
            v-if="chapterData"
            :commentable-type="'MangaChapter'"
            :commentable-id="chapterData.id"
          />
        </div>
      </div>
    </main>

    <!-- Bottom Navigation -->
    <div class="sticky bottom-0 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 shadow-lg">
      <div class="container mx-auto px-4 py-4 flex items-center justify-between">
        <NuxtLink
          v-if="previousChapter"
          :to="`/${storySlug}/${previousChapter.id}`"
          class="flex items-center gap-2 text-primary hover:opacity-80"
        >
          <UIcon name="i-heroicons-arrow-left" class="w-5 h-5" />
          <span>Chương trước</span>
        </NuxtLink>
        <div v-else></div>

        <!-- Chapter Selector -->
        <USelect
          v-model="selectedChapter"
          :options="allChapters"
          value-attribute="slug"
          option-attribute="name"
          size="sm"
          class="min-w-[200px]"
          @change="goToChapter"
        />

        <NuxtLink
          v-if="nextChapter"
          :to="`/${storySlug}/${nextChapter.id}`"
          class="flex items-center gap-2 text-primary hover:opacity-80"
        >
          <span>Chương tiếp</span>
          <UIcon name="i-heroicons-arrow-right" class="w-5 h-5" />
        </NuxtLink>
        <div v-else></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">


const route = useRoute()
const storySlug = route.params.slug as string
const chapterSlug = route.params.chapter as string
const { addHistory } = useReadingHistory()

// Reading Settings
const fontSize = ref(16)
const lineHeight = ref(1.8)
const bgColor = ref('#ffffff')
const isDarkMode = ref(false)

// Load chapter data
const { data: chapterData, pending: chapterPending } = useLazyHttp<{
  ok: boolean
  data: {
    id: number
    name: string
    slug: string
    order: number
    description: string | null
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
}>(`/mangas/${storySlug}/chapters/${chapterSlug}`)

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
}>(`/mangas/${storySlug}`)

const allChapters = computed(() => {
  return mangaData.value?.data?.chapters || []
})

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

const fontSizeOptions = [
  [
    { label: 'Nhỏ (14px)', click: () => fontSize.value = 14 },
    { label: 'Vừa (16px)', click: () => fontSize.value = 16 },
    { label: 'Lớn (18px)', click: () => fontSize.value = 18 },
    { label: 'Rất lớn (20px)', click: () => fontSize.value = 20 },
  ]
]

const bgColorOptions = [
  [
    { label: 'Trắng', click: () => { bgColor.value = '#ffffff'; isDarkMode.value = false } },
    { label: 'Xanh nhạt', click: () => bgColor.value = '#f0f4f8' },
    { label: 'Vàng nhạt', click: () => bgColor.value = '#fdf7e6' },
    { label: 'Xám tối', click: () => { bgColor.value = '#1e293b'; isDarkMode.value = true } },
  ]
]

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatNumber = (num: number) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

const toggleFullscreen = () => {
  document.documentElement.requestFullscreen?.()
}

const goToChapter = () => {
  navigateTo(`/${storySlug}/${selectedChapter.value}`)
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

<style scoped>
.prose {
  color: inherit;
}

.dark .prose {
  color: inherit;
}
</style>
