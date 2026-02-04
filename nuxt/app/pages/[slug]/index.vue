<template>
    <div>
        <!-- Main Content -->
        <div class="min-h-screen  py-8">
            <div class="container mx-auto px-4 md:px-6 lg:px-8 max-w-7xl">
                <!-- Breadcrumb -->
                <nav class="mb-8 flex items-center gap-2 text-sm">
                    <NuxtLink to="/" class="text-zinc-600 dark:text-zinc-400 hover:text-primary">
                        Trang chủ
                    </NuxtLink>
                    <UIcon name="i-heroicons-chevron-right" class="w-4 h-4 text-zinc-400" />
                    <span class="text-zinc-900 dark:text-white">{{ manga?.title || 'Loading...' }}</span>
                </nav>

                <!-- Loading State -->
                <div v-if="mangaPending" class="flex items-center justify-center py-12">
                    <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-primary" />
                </div>

                <!-- Grid Layout -->
                <div v-else-if="manga" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-6">
                            <!-- Manga Info -->
                            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
                                <Image :src="manga.avatar || 'https://via.placeholder.com/400x600'" :alt="manga.title"
                                    container-class="w-full h-full" width="400" height="600" />
                                <div class="p-4">
                                    <h2 class="font-bold text-zinc-900 dark:text-white mb-1">{{ manga.title }}</h2>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">{{ manga.author }}</p>

                                    <div class="grid grid-cols-2 gap-3 mb-4 text-center text-sm">
                                        <div>
                                            <div class="flex items-center justify-center gap-1">
                                                <UIcon name="i-heroicons-star-solid" class="w-4 h-4 text-yellow-400" />
                                                <span class="font-bold">{{ manga.rating }}</span>
                                            </div>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400">Rating</p>
                                        </div>
                                        <div>
                                            <div class="font-bold">{{ formatNumber(manga.views) }}</div>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400">Lượt xem</p>
                                        </div>
                                    </div>

                                    <UButton color="primary" icon="i-heroicons-play" label="Đọc ngay" block
                                        @click="readFromStart" />
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">Trạng thái</span>
                                    <UBadge :label="manga.status"
                                        :color="manga.status === 'Đang ra' ? 'success' : 'secondary'" size="sm" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">Cập nhật</span>
                                    <span class="text-sm font-semibold">{{ manga.lastUpdate }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">Chương mới</span>
                                    <span class="text-sm font-semibold">{{ manga.lastChapter }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Title & Description -->
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-2">
                                {{ manga.title }}
                            </h1>
                            <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-6">{{ manga.author }}</p>

                            <div class="mb-6">
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <UBadge v-for="cat in manga.categories" :key="cat" :label="cat" variant="soft"
                                        size="sm" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold text-zinc-900 dark:text-white">Mô tả</h3>
                                <button @click="descriptionExpanded = !descriptionExpanded"
                                    class="text-sm text-primary">
                                    {{ descriptionExpanded ? 'Ẩn' : 'Xem thêm' }}
                                </button>
                            </div>
                            <p :class="[
                                'text-zinc-700 dark:text-zinc-300 leading-relaxed',
                                !descriptionExpanded && 'line-clamp-4'
                            ]">
                                {{ manga.description }}
                            </p>
                        </div>

                        <!-- Rating Section (Form để đánh giá) -->
                        <div class="mb-6">
                            <RatingSection v-if="mangaId > 0" :manga-id="mangaId" />
                        </div>

                        <!-- Tabs -->
                        <div class="border-b border-zinc-200 dark:border-zinc-700">
                            <div class="flex gap-6">
                                <button @click="activeTab = 'chapters'" :class="[
                                    'py-3 px-2 font-semibold text-sm border-b-2 transition-colors',
                                    activeTab === 'chapters'
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                                ]">
                                    Chương ({{ chapters.length || 0 }})
                                </button>
                                <button @click="activeTab = 'rating'" :class="[
                                    'py-3 px-2 font-semibold text-sm border-b-2 transition-colors',
                                    activeTab === 'rating'
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                                ]">
                                    Đánh giá
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                        ({{ manga.totalRatings || 0 }})
                                    </span>
                                </button>
                                <button @click="activeTab = 'comments'" data-tab="comments" :class="[
                                    'py-3 px-2 font-semibold text-sm border-b-2 transition-colors',
                                    activeTab === 'comments'
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                                ]">
                                    Bình luận
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">({{ manga.comment_count ||
                                        0 }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- Tab Content -->
                        <div v-if="activeTab === 'chapters'">
                            <StoryChapterList v-if="manga" :chapters="chapters" :manga-slug="manga?.slug || ''" />
                        </div>
                        <div v-else-if="activeTab === 'rating'">
                            <RatingList v-if="mangaId > 0" :manga-id="mangaId" />
                        </div>
                        <div v-else-if="activeTab === 'comments'" id="comments-section">
                            <CommentSection :commentable-type="'Manga'" :commentable-id="mangaId" />
                        </div>

                        <!-- Related Manga -->
                        <div v-if="manga && relatedManga.length > 0"
                            class="border-t border-zinc-200 dark:border-zinc-700 pt-8">
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">Truyện liên quan</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <StoryCard v-for="s in relatedManga" :key="s.id" :story="s" variant="grid" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: 'default'
})

const route = useRoute()
const slug = route.params.slug as string

// Load manga data
const { data: mangaData, pending: mangaPending } = useLazyHttp<{
    ok: boolean
    data: {
        id: number
        name: string
        slug: string
        avatar: string | null
        description: string
        status: string
        views: number
        rating: number
        total_ratings: number
        follows: number
        author: {
            id: number
            name: string
        } | null
        badge: {
            id: number
            name: string
        } | null
        categories: Array<{
            id: number
            name: string
            slug: string
        }>
        comment_count: number
        chapters: Array<{
            id: number
            name: string
            slug: string
            order: number
            created_at: string
        }>
    }
}>(`/mangas/${slug}`)

const mangaId = computed(() => mangaData.value?.data?.id || 0)
const manga = computed(() => {
    const data = mangaData.value?.data
    if (!data) return null
    return {
        id: data.id,
        title: data.name,
        slug: data.slug,
        author: data.author?.name || 'Unknown',
        avatar: data.avatar,
        status: data.status,
        rating: data.rating || 0,
        totalRatings: data.total_ratings || 0,
        categories: data.categories.map(c => c.name),
        description: data.description || '',
        views: data.views || 0,
        comment_count: data.comment_count || 0,
        lastChapter: (() => {
            const lastChapter = data.chapters[data.chapters.length - 1]
            return lastChapter ? lastChapter.name : 'Chưa có'
        })(),
        lastUpdate: (() => {
            const lastChapter = data.chapters[data.chapters.length - 1]
            return lastChapter ? formatRelativeTime(lastChapter.created_at) : ''
        })(),
        chapters: data.chapters.map(ch => ({
            id: ch.id,
            number: ch.order,
            title: ch.name,
            slug: ch.slug,
            uploadedAt: ch.created_at
        }))
    }
})

const chapters = computed(() => manga.value?.chapters || [])
const activeTab = ref<'chapters' | 'rating' | 'comments'>('chapters')
const descriptionExpanded = ref(false)
const relatedManga = ref<any[]>([])

// Handle hash navigation (e.g., from notification)
onMounted(() => {
    const hash = window.location.hash
    if (hash.startsWith('#comment-')) {
        activeTab.value = 'comments'
        // Scroll to comment after a delay to ensure it's loaded
        nextTick(() => {
            setTimeout(() => {
                const commentId = hash.replace('#comment-', '')
                const element = document.getElementById(`comment-${commentId}`)
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
                    element.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2', 'rounded-lg')
                    setTimeout(() => {
                        element.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2')
                    }, 2000)
                }
            }, 500)
        })
    } else if (hash === '#comments') {
        activeTab.value = 'comments'
        nextTick(() => {
            setTimeout(() => {
                const commentsSection = document.getElementById('comments-section')
                if (commentsSection) {
                    commentsSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
                }
            }, 500)
        })
    }
})

const formatNumber = (num: number) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
    return num.toString()
}

const formatRelativeTime = (dateString: string) => {
    const date = new Date(dateString)
    const now = new Date()
    const diff = now.getTime() - date.getTime()
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))

    if (days === 0) return 'Hôm nay'
    if (days === 1) return 'Hôm qua'
    if (days < 7) return `${days} ngày trước`
    return date.toLocaleDateString('vi-VN')
}

const readFromStart = () => {
    if (chapters.value.length > 0 && chapters.value[0]) {
        navigateTo(`/${manga.value?.slug}/${chapters.value[0].slug}`)
    }
}

useHead({
    title: computed(() => `${manga.value?.title || 'Loading...'} - Đọc Truyện Online`),
    meta: [
        {
            name: 'description',
            content: computed(() => manga.value?.description?.substring(0, 150) || '')
        }
    ]
})
</script>