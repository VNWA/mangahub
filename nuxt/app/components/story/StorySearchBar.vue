<template>
  <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
    <div class="space-y-4">
      <!-- Main Search -->
      <UInput v-model="searchQuery" icon="i-heroicons-magnifying-glass" placeholder="Tìm truyện, tác giả, thể loại..."
        @keyup.enter="search" size="lg" />

      <!-- Filters (collapsible) -->
      <div v-if="showFilters" class="space-y-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
        <!-- Status Filter -->
        <div>
          <label class="text-sm font-semibold text-zinc-900 dark:text-white mb-2 block">Trạng thái</label>
          <div class="flex flex-wrap gap-2">
            <UButton v-for="status in statuses" :key="status.id" :label="status.name"
              :color="selectedStatus === status.id ? 'primary' : 'neutral'"
              :variant="selectedStatus === status.id ? 'soft' : 'ghost'" size="sm"
              @click="selectedStatus = status.id" />
          </div>
        </div>

        <!-- Sort Filter -->
        <div>
          <label class="text-sm font-semibold text-zinc-900 dark:text-white mb-2 block">Sắp xếp</label>
          <USelect v-model="selectedSort" :options="sortOptions" size="sm" />
        </div>

        <!-- Categories Filter -->
        <div>
          <label class="text-sm font-semibold text-zinc-900 dark:text-white mb-2 block">Thể loại</label>
          <div class="flex flex-wrap gap-2">
            <UButton v-for="cat in categories" :key="cat.id" :label="cat.name"
              :color="selectedCategories.includes(cat.id) ? 'primary' : 'neutral'"
              :variant="selectedCategories.includes(cat.id) ? 'soft' : 'ghost'" size="sm"
              @click="toggleCategory(cat.id)" />
          </div>
        </div>
      </div>

      <!-- Toggle Filters & Search Button -->
      <div class="flex gap-2">
        <UButton :icon="showFilters ? 'i-heroicons-funnel' : 'i-heroicons-funnel'"
          :color="showFilters ? 'primary' : 'neutral'" variant="ghost" @click="showFilters = !showFilters"
          label="Bộ lọc" />
        <UButton color="primary" class="flex-1" label="Tìm kiếm" @click="search" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const searchQuery = ref('')
const showFilters = ref(false)
const selectedStatus = ref('all')
const selectedSort = ref('newest')
const selectedCategories = ref<string[]>([])

const statuses = [
  { id: 'all', name: 'Tất cả' },
  { id: 'ongoing', name: 'Đang ra' },
  { id: 'completed', name: 'Hoàn thành' },
  { id: 'hiatus', name: 'Tạm dừng' }
]

const sortOptions = [
  { value: 'newest', label: 'Mới nhất' },
  { value: 'popular', label: 'Phổ biến' },
  { value: 'rating', label: 'Đánh giá cao' },
  { value: 'views', label: 'Lượt xem' },
  { value: 'alphabetical', label: 'Theo tên' }
]

const categories = [
  { id: 'action', name: 'Action' },
  { id: 'comedy', name: 'Comedy' },
  { id: 'drama', name: 'Drama' },
  { id: 'fantasy', name: 'Fantasy' },
  { id: 'romance', name: 'Romance' },
  { id: 'scifi', name: 'Sci-Fi' },
  { id: 'horror', name: 'Horror' },
  { id: 'mystery', name: 'Mystery' }
]

const emit = defineEmits<{
  search: [query: string, filters: any]
}>()

const toggleCategory = (id: string) => {
  const index = selectedCategories.value.indexOf(id)
  if (index > -1) {
    selectedCategories.value.splice(index, 1)
  } else {
    selectedCategories.value.push(id)
  }
}

const search = () => {
  emit('search', searchQuery.value, {
    status: selectedStatus.value,
    sort: selectedSort.value,
    categories: selectedCategories.value
  })
}
</script>
