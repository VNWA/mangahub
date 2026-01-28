<template>
    <div>
        <!-- Main Content -->
        <main class="min-h-screen bg-white dark:bg-slate-900 py-8">
            <div class="container mx-auto px-4 md:px-6 lg:px-8 max-w-7xl">
                <!-- Breadcrumb -->
                <nav class="mb-8 flex items-center gap-2 text-sm">
                    <NuxtLink to="/" class="text-slate-600 dark:text-slate-400 hover:text-primary">
                        Trang chủ
                    </NuxtLink>
                    <UIcon name="i-heroicons-chevron-right" class="w-4 h-4 text-slate-400" />
                    <span class="text-slate-900 dark:text-white">Tìm kiếm</span>
                </nav>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Sidebar Filter -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">
                            <!-- Search Box -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                                    Tìm kiếm
                                </label>
                                <UInput v-model="searchQuery" type="text" placeholder="Nhập tên truyện..."
                                    icon="i-heroicons-magnifying-glass" />
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                                    Trạng thái
                                </label>
                                <div class="space-y-2">
                                    <label v-for="status in ['Tất cả', 'Đang ra', 'Hoàn thành', 'Tạm dừng']"
                                        :key="status" class="flex items-center gap-2 cursor-pointer">
                                        <URadio :model-value="selectedStatus" :native-value="status"
                                            @update:model-value="selectedStatus = status" />
                                        <span class="text-sm">{{ status }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Categories Filter -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                                    Thể loại
                                </label>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    <label v-for="cat in categories" :key="cat"
                                        class="flex items-center gap-2 cursor-pointer">
                                        <UCheckbox :model-value="selectedCategories" :native-value="cat"
                                            @update:model-value="(v) => selectedCategories = v" />
                                        <span class="text-sm">{{ cat }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Sort -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                                    Sắp xếp
                                </label>
                                <USelect v-model="selectedSort"
                                    :options="['Mới nhất', 'Hot nhất', 'Đánh giá cao', 'Lượt xem']" />
                            </div>

                            <!-- Reset Button -->
                            <UButton color="neutral" variant="ghost" label="Đặt lại" block @click="resetFilters" />
                        </div>
                    </div>

                    <!-- Results -->
                    <div class="lg:col-span-3">
                        <!-- Results Info -->
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                                    Kết quả tìm kiếm
                                </h1>
                                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
                                    Tìm thấy {{ totalResults }} truyện
                                </p>
                            </div>
                            <USelect v-model="viewMode" :options="['Grid', 'List']" size="sm" />
                        </div>

                        <!-- Results Grid/List -->
                        <div :class="[
                            viewMode === 'Grid'
                                ? 'grid grid-cols-2 md:grid-cols-3 gap-6'
                                : 'space-y-4'
                        ]">
                            <StoryCard v-for="story in filteredStories" :key="story.id" :story="story"
                                :variant="viewMode === 'Grid' ? 'grid' : 'list'" />
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalResults > 0" class="mt-12 flex justify-center gap-2">
                            <UButton color="neutral" variant="ghost" icon="i-heroicons-arrow-left" square
                                :disabled="currentPage === 1" @click="currentPage = currentPage - 1" />
                            <div class="flex items-center gap-2">
                                <span v-for="page in totalPages" :key="page" @click="currentPage = page" :class="[
                                    'w-10 h-10 flex items-center justify-center rounded cursor-pointer transition-colors',
                                    page === currentPage
                                        ? 'bg-primary text-white'
                                        : 'hover:bg-slate-200 dark:hover:bg-slate-700'
                                ]">
                                    {{ page }}
                                </span>
                            </div>
                            <UButton color="neutral" variant="ghost" icon="i-heroicons-arrow-right" square
                                :disabled="currentPage === totalPages" @click="currentPage = currentPage + 1" />
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <UIcon name="i-heroicons-magnifying-glass"
                                class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                            <p class="text-slate-600 dark:text-slate-400">Không tìm thấy truyện nào</p>
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

const searchQuery = ref('')
const selectedStatus = ref('Tất cả')
const selectedCategories = ref<string[]>([])
const selectedSort = ref('Mới nhất')
const currentPage = ref(1)
const viewMode = ref('Grid')

const categories = [
    'Action', 'Comedy', 'Drama', 'Fantasy', 'Romance',
    'Sci-Fi', 'Horror', 'Mystery', 'School', 'Supernatural'
]

// Mock data
const allStories = ref(Array.from({ length: 45 }, (_, i) => ({
    id: `${i + 1}`,
    title: `Truyện ${i + 1}`,
    slug: `truyen-${i + 1}`,
    author: 'Tác giả',
    coverImage: `https://images.unsplash.com/photo-1621038149384-90c0f91bb3f4?w=400&h=600&fit=crop`,
    status: ['Đang ra', 'Hoàn thành', 'Tạm dừng'][i % 3],
    rating: (7 + Math.random() * 2).toFixed(1),
    categories: ['Action', 'Adventure'],
    description: 'Mô tả truyện',
    views: Math.floor(Math.random() * 5000000),
    lastChapter: `Chương ${i + 10}`,
})))

const filteredStories = computed(() => {
    let result = allStories.value

    if (searchQuery.value) {
        result = result.filter(s =>
            s.title.toLowerCase().includes(searchQuery.value.toLowerCase())
        )
    }

    if (selectedStatus.value !== 'Tất cả') {
        result = result.filter(s => s.status === selectedStatus.value)
    }

    if (selectedCategories.value.length > 0) {
        result = result.filter(s =>
            selectedCategories.value.some(cat => s.categories.includes(cat))
        )
    }

    return result
})

const totalResults = computed(() => filteredStories.value.length)
const itemsPerPage = 12
const totalPages = computed(() => Math.ceil(totalResults.value / itemsPerPage))

const paginatedStories = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    return filteredStories.value.slice(start, start + itemsPerPage)
})

const resetFilters = () => {
    searchQuery.value = ''
    selectedStatus.value = 'Tất cả'
    selectedCategories.value = []
    selectedSort.value = 'Mới nhất'
    currentPage.value = 1
}

useHead({
    title: 'Tìm kiếm - WebTruyen'
})
</script>