<template>
  <div class="space-y-4">
    <!-- Filter -->
    <div class="flex gap-2">
      <UButton
        v-for="filter in filters"
        :key="filter"
        :color="activeFilter === filter ? 'primary' : 'neutral'"
        :variant="activeFilter === filter ? 'soft' : 'ghost'"
        :label="filter"
        size="sm"
        @click="activeFilter = filter"
      />
    </div>

    <!-- Stories Grid -->
    <div v-if="favorites.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="story in filteredFavorites"
        :key="story.id"
        class="group relative"
      >
        <img
          :src="story.coverImage"
          :alt="story.title"
          class="w-full h-48 object-cover rounded-lg"
        />
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors rounded-lg flex items-end p-3">
          <div class="text-white opacity-0 group-hover:opacity-100 transition-opacity w-full">
            <h3 class="font-semibold text-sm line-clamp-1">{{ story.title }}</h3>
            <div class="flex items-center justify-between mt-2">
              <div class="flex items-center gap-1">
                <UIcon name="i-heroicons-star-solid" class="w-4 h-4 text-yellow-400" />
                <span class="text-xs">{{ story.rating }}</span>
              </div>
              <button
                @click.stop="removeFavorite(story.id)"
                class="text-red-400 hover:text-red-300"
              >
                <UIcon name="i-heroicons-heart-solid" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <UIcon name="i-heroicons-heart" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
      <p class="text-slate-600 dark:text-slate-400">Chưa có truyện yêu thích</p>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Story {
  id: string
  title: string
  coverImage: string
  rating: number
  status: string
  author: string
}

interface Props {
  favorites: Story[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'remove-favorite': [id: string]
}>()

const activeFilter = ref('Tất cả')
const filters = ['Tất cả', 'Đang ra', 'Hoàn thành']

const filteredFavorites = computed(() => {
  if (activeFilter.value === 'Tất cả') {
    return props.favorites
  }
  return props.favorites.filter(f => f.status === activeFilter.value)
})

const removeFavorite = (id: string) => {
  emit('remove-favorite', id)
}
</script>
