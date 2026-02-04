<template>
    <div>
        <!-- Main Content -->
        <div class="min-h-screen  py-8">
            <div class="container mx-auto px-4 md:px-6 lg:px-8 max-w-5xl">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-2">🏆 Bảng xếp hạng</h1>
                    <p class="text-zinc-600 dark:text-zinc-400">Top truyện được yêu thích nhất</p>
                </div>

                <!-- Tabs -->
                <div class="mb-8">
                    <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700 overflow-x-auto">
                        <button v-for="(tab, index) in tabs" :key="tab.value" @click="activeTabIndex = index" :class="[
                            'px-4 py-3 font-semibold text-sm border-b-2 transition-all whitespace-nowrap flex items-center gap-2',
                            activeTabIndex === index
                                ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400'
                                : 'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                        ]">
                            <UIcon :name="tab.icon" class="w-4 h-4" />
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="space-y-3">
                    <div v-for="i in 5" :key="i" class="animate-pulse">
                        <div
                            class="flex items-start gap-4 p-4 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <div class="w-14 h-14 bg-zinc-200 dark:bg-zinc-700 rounded-xl"></div>
                            <div class="w-20 h-32 bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-6 bg-zinc-200 dark:bg-zinc-700 rounded w-3/4"></div>
                                <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-1/2"></div>
                                <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-full"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ranking List -->
                <div v-else-if="currentRankedStories.length > 0" class="space-y-3">
                    <div v-for="(story, index) in currentRankedStories" :key="story.id"
                        class="flex items-start gap-4 p-4 bg-white dark:bg-zinc-800 rounded-xl hover:shadow-lg transition-all border border-zinc-200 dark:border-zinc-700 hover:border-purple-300 dark:hover:border-purple-700">
                        <!-- Rank Badge -->
                        <div :class="[
                            'flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center font-bold text-xl shadow-md',
                            index === 0 && 'bg-gradient-to-br from-yellow-400 to-yellow-600 text-white',
                            index === 1 && 'bg-gradient-to-br from-zinc-300 to-zinc-500 text-white',
                            index === 2 && 'bg-gradient-to-br from-orange-400 to-orange-600 text-white',
                            index > 2 && 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-white'
                        ]">
                            #{{ index + 1 }}
                        </div>

                        <!-- Cover Image - Clickable -->
                        <NuxtLink :to="`/${story.slug}`" class="flex-shrink-0 group/img">
                            <Image :src="story.avatar" :alt="story.title" container-class="w-20 h-32 rounded-lg group"
                                hoverable />
                        </NuxtLink>

                        <!-- Content -->
                        <div class="flex-1">
                            <NuxtLink :to="`/${story.slug}`"
                                class="block text-lg font-bold text-zinc-900 dark:text-white hover:text-primary transition-colors mb-1">
                                {{ story.title }}
                            </NuxtLink>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">{{ story.author }}</p>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 line-clamp-2 mb-3">
                                {{ story.description }}
                            </p>

                            <!-- Stats -->
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-zinc-600 dark:text-zinc-400">Đánh giá</p>
                                    <div class="flex items-center gap-1">
                                        <UIcon name="i-heroicons-star-solid" class="w-4 h-4 text-yellow-400" />
                                        <span class="font-bold">{{ story.rating }}</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-zinc-600 dark:text-zinc-400">Lượt xem</p>
                                    <span class="font-bold">{{ formatNumber(story.views) }}</span>
                                </div>
                                <div>
                                    <p class="text-zinc-600 dark:text-zinc-400">Trạng thái</p>
                                    <UBadge :label="story.status"
                                        :color="story.status === 'Đang ra' ? 'success' : 'secondary'" size="sm" />
                                </div>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="flex-shrink-0 flex flex-col gap-2">
                            <NuxtLink :to="`/${story.slug}`"
                                class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all text-sm font-semibold whitespace-nowrap shadow-md">
                                Đọc ngay
                            </NuxtLink>
                            <NuxtLink :to="`/${story.slug}/${getLastChapterNumber(story.lastChapter)}`"
                                class="px-4 py-2 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-xs font-medium whitespace-nowrap">
                                Chương mới
                            </NuxtLink>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <UIcon name="i-heroicons-trophy" class="w-12 h-12 text-zinc-400 mx-auto mb-3" />
                    <p class="text-zinc-600 dark:text-zinc-400">Chưa có dữ liệu xếp hạng</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: 'default'
})

const activeTabIndex = ref(0)
const tabs = [
    { label: 'Top Tuần', icon: 'i-heroicons-calendar-days', value: 'weekly' },
    { label: 'Top Tháng', icon: 'i-heroicons-calendar', value: 'monthly' },
    { label: 'Top Năm', icon: 'i-heroicons-star', value: 'year' },
    { label: 'Top All', icon: 'i-heroicons-trophy', value: 'all' }
]

const rankedStories = ref<any[]>([])
const loading = ref(false)

// Load ranking data
const loadRanking = async () => {
    const tabValue = tabs[activeTabIndex.value]?.value || 'all'
    loading.value = true

    try {
        const type = tabValue === 'year' ? 'all' : tabValue
        const data = await $http<{
            ok: boolean
            data: Array<{
                id: number
                title: string
                slug: string
                author: string
                avatar: string | null
                description: string
                rating: number
                views: number
                status: string
                lastChapter?: string
            }>
        }>('/mangas/top', {
            query: {
                type,
                limit: 50,
            },
        })

        if (data?.ok && data.data) {
            rankedStories.value = data.data.map((item: any) => ({
                id: item.id,
                title: item.title || item.name,
                slug: item.slug,
                author: item.author?.name || item.author || 'Unknown',
                avatar: item.avatar,
                description: item.description || '',
                rating: item.rating || 0,
                views: item.views || item.total_views || 0,
                status: item.status || 'Đang ra',
                lastChapter: item.lastChapter || item.chapters?.[item.chapters.length - 1]?.name || 'Chưa có',
            }))
        }
    } catch (error) {
        console.error('Failed to load ranking:', error)
        rankedStories.value = []
    } finally {
        loading.value = false
    }
}

const currentRankedStories = computed(() => rankedStories.value)

// Watch for tab changes
watch(activeTabIndex, () => {
    loadRanking()
})

const formatNumber = (num: number) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
    return num.toString()
}

const getLastChapterNumber = (lastChapter: string) => {
    if (!lastChapter || lastChapter === 'Chưa có') return '1'
    const match = lastChapter.match(/\d+/)
    return match ? match[0] : '1'
}

onMounted(() => {
    loadRanking()
})

useHead({
    title: 'Bảng xếp hạng - WebTruyen'
})
</script>