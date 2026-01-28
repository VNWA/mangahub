<template>
    <div>
        <!-- Main Content -->
        <main class="min-h-screen bg-white dark:bg-slate-900 py-8">
            <div class="container mx-auto px-4 md:px-6 lg:px-8 max-w-5xl">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-2">🏆 Bảng xếp hạng</h1>
                    <p class="text-slate-600 dark:text-slate-400">Top truyện được yêu thích nhất</p>
                </div>

                <!-- Tabs -->
                <div class="mb-8">
                    <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
                        <button v-for="(tab, index) in tabs" :key="tab.value" @click="activeTabIndex = index" :class="[
                            'px-4 py-3 font-semibold text-sm border-b-2 transition-all whitespace-nowrap flex items-center gap-2',
                            activeTabIndex === index
                                ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400'
                                : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]">
                            <UIcon :name="tab.icon" class="w-4 h-4" />
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Ranking List -->
                <div class="space-y-3">
                    <div v-for="(story, index) in currentRankedStories" :key="story.id"
                        class="flex items-start gap-4 p-4 bg-white dark:bg-slate-800 rounded-xl hover:shadow-lg transition-all border border-slate-200 dark:border-slate-700 hover:border-purple-300 dark:hover:border-purple-700">
                        <!-- Rank Badge -->
                        <div :class="[
                            'flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center font-bold text-xl shadow-md',
                            index === 0 && 'bg-gradient-to-br from-yellow-400 to-yellow-600 text-white',
                            index === 1 && 'bg-gradient-to-br from-slate-300 to-slate-500 text-white',
                            index === 2 && 'bg-gradient-to-br from-orange-400 to-orange-600 text-white',
                            index > 2 && 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white'
                        ]">
                            #{{ index + 1 }}
                        </div>

                        <!-- Cover Image - Clickable -->
                        <NuxtLink :to="`/${story.slug}`" class="flex-shrink-0 group/img">
                            <img :src="story.coverImage" :alt="story.title"
                                class="w-20 h-32 object-cover rounded-lg group-hover/img:scale-105 transition-transform duration-300 shadow-md" />
                        </NuxtLink>

                        <!-- Content -->
                        <div class="flex-1">
                            <NuxtLink :to="`/${story.slug}`"
                                class="block text-lg font-bold text-slate-900 dark:text-white hover:text-primary transition-colors mb-1">
                                {{ story.title }}
                            </NuxtLink>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">{{ story.author }}</p>
                            <p class="text-sm text-slate-700 dark:text-slate-300 line-clamp-2 mb-3">
                                {{ story.description }}
                            </p>

                            <!-- Stats -->
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-slate-600 dark:text-slate-400">Đánh giá</p>
                                    <div class="flex items-center gap-1">
                                        <UIcon name="i-heroicons-star-solid" class="w-4 h-4 text-yellow-400" />
                                        <span class="font-bold">{{ story.rating }}</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-slate-600 dark:text-slate-400">Lượt xem</p>
                                    <span class="font-bold">{{ formatNumber(story.views) }}</span>
                                </div>
                                <div>
                                    <p class="text-slate-600 dark:text-slate-400">Trạng thái</p>
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
                                class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors text-xs font-medium whitespace-nowrap">
                                Chương mới
                            </NuxtLink>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: 'default'
})

const activeTabIndex = ref(0)
const tabs = [
    { label: 'Top Tuần', icon: 'i-heroicons-calendar-days', value: 'week' },
    { label: 'Top Tháng', icon: 'i-heroicons-calendar', value: 'month' },
    { label: 'Top Năm', icon: 'i-heroicons-star', value: 'year' },
    { label: 'Top All', icon: 'i-heroicons-trophy', value: 'all' }
]

const weekStories = ref([
    {
        id: '1',
        title: 'One Piece',
        slug: 'one-piece',
        author: 'Eiichiro Oda',
        coverImage: 'https://images.unsplash.com/photo-1621038149384-90c0f91bb3f4?w=400&h=600&fit=crop',
        description: 'Cuộc phiêu lưu của Monkey D. Luffy và đội hải tặc của anh ta',
        rating: 9.0,
        views: 5000000,
        status: 'Đang ra',
    },
    {
        id: '2',
        title: 'My Hero Academia',
        slug: 'my-hero-academia',
        author: 'Kohei Horikoshi',
        coverImage: 'https://images.unsplash.com/photo-1623956299424-58e5a4e3f4c7?w=400&h=600&fit=crop',
        description: 'Nước này người có siêu năng lực, Deku là ngoại lệ duy nhất',
        rating: 8.7,
        views: 3500000,
        status: 'Đang ra',
    },
    {
        id: '3',
        title: 'Attack on Titan',
        slug: 'attack-on-titan',
        author: 'Hajime Isayama',
        coverImage: 'https://images.unsplash.com/photo-1634447288519-eec6d71f7ab3?w=400&h=600&fit=crop',
        description: 'Thế giới bị tấn công bởi những con quái vật khổng lồ',
        rating: 9.1,
        views: 4500000,
        status: 'Hoàn thành',
    },
    {
        id: '4',
        title: 'Jujutsu Kaisen',
        slug: 'jujutsu-kaisen',
        author: 'Gege Akutami',
        coverImage: 'https://images.unsplash.com/photo-1621038149384-90c0f91bb3f4?w=400&h=600&fit=crop',
        description: 'Yuji nuốt một ngón tay của quỷ vương và trở thành một kỳ sinh',
        rating: 8.8,
        views: 3800000,
        status: 'Đang ra',
    },
    {
        id: '5',
        title: 'Demon Slayer',
        slug: 'demon-slayer',
        author: 'Koyoharu Gotouge',
        coverImage: 'https://images.unsplash.com/photo-1634447288519-eec6d71f7ab3?w=400&h=600&fit=crop',
        description: 'Tanjiro truy đuổi quỷ để cứu em gái bị biến thành quỷ',
        rating: 8.8,
        views: 3800000,
        status: 'Hoàn thành',
        lastChapter: 'Chapter 205',
    },
])

const monthStories = ref([...weekStories.value].reverse())
const yearStories = ref([...weekStories.value])
const allStories = ref([...weekStories.value])

const currentRankedStories = computed(() => {
    const tabValue = tabs[activeTabIndex.value]?.value
    switch (tabValue) {
        case 'week':
            return weekStories.value
        case 'month':
            return monthStories.value
        case 'year':
            return yearStories.value
        case 'all':
            return allStories.value
        default:
            return weekStories.value
    }
})

const formatNumber = (num: number) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
    return num.toString()
}

const getLastChapterNumber = (lastChapter: string) => {
    const match = lastChapter.match(/\d+/)
    return match ? match[0] : '1'
}

useHead({
    title: 'Bảng xếp hạng - WebTruyen'
})
</script>