<template>
  <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
    <div class="mb-6">
      <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Bộ Lọc</h3>

      <!-- Status Filter -->
      <div class="mb-6">
        <h4 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Trạng thái</h4>
        <div class="space-y-2">
          <label v-for="status in statusOptions" :key="status.id" class="flex items-center gap-3 cursor-pointer">
            <UCheckbox v-model="filters.status" :value="status.id" />
            <span class="text-sm text-slate-700 dark:text-slate-300">{{ status.label }}</span>
          </label>
        </div>
      </div>

      <!-- Category Filter -->
      <div class="mb-6">
        <h4 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Thể loại</h4>
        <TagFilter
          v-model="filters.categories"
          :items="categories"
          :multiple="true"
        />
      </div>

      <!-- Rating Filter -->
      <div class="mb-6">
        <h4 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Đánh giá</h4>
        <div class="flex items-center gap-4">
          <span class="text-sm font-semibold">{{ minRating }}</span>
          <input
            v-model.number="minRating"
            type="range"
            min="0"
            max="10"
            step="0.5"
            class="flex-1 cursor-pointer"
          />
          <span class="text-sm font-semibold">10</span>
        </div>
      </div>

      <!-- Sort Options -->
      <div class="mb-6">
        <h4 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Sắp xếp theo</h4>
        <USelect
          v-model="filters.sortBy"
          :options="sortOptions"
          size="sm"
        />
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-2">
        <UButton
          color="primary"
          label="Áp dụng"
          block
          @click="applyFilters"
        />
        <UButton
          variant="soft"
          color="neutral"
          icon="i-heroicons-arrow-path"
          @click="resetFilters"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const filters = reactive({
  status: [],
  categories: [],
  sortBy: 'trending'
})

const minRating = ref(0)

const statusOptions = [
  { id: 'updating', label: 'Đang cập nhật' },
  { id: 'completed', label: 'Hoàn thành' },
  { id: 'on-hold', label: 'Tạm dừng' }
]

const categories = [
  { id: 'action', label: 'Hành động' },
  { id: 'romance', label: 'Tình cảm' },
  { id: 'comedy', label: 'Hài hước' },
  { id: 'fantasy', label: 'Kỳ ảo' },
  { id: 'mystery', label: 'Bí ẩn' },
  { id: 'horror', label: 'Kinh dị' }
]

const sortOptions = [
  { value: 'trending', label: 'Xu hướng' },
  { value: 'latest', label: 'Mới nhất' },
  { value: 'rating', label: 'Đánh giá cao' },
  { value: 'views', label: 'Lượt xem' }
]

const emit = defineEmits<{
  apply: [filters: any]
  reset: []
}>()

const applyFilters = () => {
  emit('apply', { ...filters, minRating: minRating.value })
}

const resetFilters = () => {
  filters.status = []
  filters.categories = []
  filters.sortBy = 'trending'
  minRating.value = 0
  emit('reset')
}
</script>
