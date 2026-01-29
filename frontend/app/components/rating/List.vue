<template>
    <div class="space-y-4">
        <!-- Loading State -->
        <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="animate-pulse">
                <div class="bg-slate-200 dark:bg-slate-700 rounded-lg h-32"></div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="ratings.length === 0" class="text-center py-12">
            <UIcon name="i-heroicons-star" class="w-12 h-12 text-slate-400 mx-auto mb-3" />
            <p class="text-slate-600 dark:text-slate-400">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
        </div>

        <!-- Ratings List -->
        <div v-else class="space-y-4">
            <div v-for="rating in ratings" :key="rating.id"
                class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex gap-3">
                    <!-- User Avatar -->
                    <UAvatar
                        :src="rating.user?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${rating.user?.email || 'user'}`"
                        :alt="rating.user?.name || 'User'" size="sm" />

                    <div class="flex-1 min-w-0">
                        <!-- User Name & Rating -->
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-slate-900 dark:text-white">
                                    {{ rating.user?.name || 'Anonymous' }}
                                </span>
                                <div class="flex gap-1">
                                    <UIcon v-for="i in 5" :key="i"
                                        :name="i <= rating.rating ? 'i-heroicons-star-solid' : 'i-heroicons-star'"
                                        :class="[
                                            'w-4 h-4',
                                            i <= rating.rating ? 'text-yellow-400' : 'text-slate-300 dark:text-slate-600'
                                        ]" />
                                </div>
                            </div>
                            <span class="text-xs text-slate-500">
                                {{ formatTime(rating.created_at) }}
                            </span>
                        </div>

                        <!-- Review Content -->
                        <div v-if="rating.review"
                            class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap wrap-break-word">
                            {{ rating.review }}
                        </div>
                        <div v-else class="text-sm text-slate-500 dark:text-slate-400 italic">
                            Không có đánh giá chi tiết
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Load More -->
        <div v-if="hasMore && !loading" class="text-center mt-6">
            <UButton @click="loadMore" variant="ghost" color="primary" :loading="loadingMore">
                <UIcon name="i-heroicons-arrow-down" class="w-4 h-4 mr-1" />
                Xem thêm đánh giá
            </UButton>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    mangaId: number
}

const props = defineProps<Props>()

interface Rating {
    id: number
    rating: number
    review: string | null
    user: {
        id: number
        name: string
        email: string
        avatar: string | null
    } | null
    created_at: string
    updated_at: string
}

const ratings = ref<Rating[]>([])
const loading = ref(true)
const loadingMore = ref(false)
const currentPage = ref(1)
const hasMore = ref(false)

// Load ratings
const loadRatings = async (page = 1, append = false) => {
    try {
        if (page === 1) {
            loading.value = true
        } else {
            loadingMore.value = true
        }

        const data = await $http<{
            ok: boolean
            data: Rating[]
            pagination: {
                current_page: number
                last_page: number
                per_page: number
                total: number
            }
        }>(`/mangas/${props.mangaId}/rating/list`, {
            query: {
                page,
                per_page: 20
            }
        })

        if (data?.ok && data.data) {
            if (append) {
                ratings.value = [...ratings.value, ...data.data]
            } else {
                ratings.value = data.data
            }
            currentPage.value = data.pagination.current_page
            hasMore.value = data.pagination.current_page < data.pagination.last_page
        }
    } catch (error) {
        console.error('Failed to load ratings:', error)
    } finally {
        loading.value = false
        loadingMore.value = false
    }
}

// Load more
const loadMore = () => {
    if (hasMore.value && !loadingMore.value) {
        loadRatings(currentPage.value + 1, true)
    }
}

const formatTime = (dateString: string) => {
    const date = new Date(dateString)
    const now = new Date()
    const diff = now.getTime() - date.getTime()
    const seconds = Math.floor(diff / 1000)
    const minutes = Math.floor(seconds / 60)
    const hours = Math.floor(minutes / 60)
    const days = Math.floor(hours / 24)

    if (days > 7) {
        return date.toLocaleDateString('vi-VN')
    } else if (days > 0) {
        return `${days} ngày trước`
    } else if (hours > 0) {
        return `${hours} giờ trước`
    } else if (minutes > 0) {
        return `${minutes} phút trước`
    } else {
        return 'Vừa xong'
    }
}

onMounted(() => {
    loadRatings()
})
</script>