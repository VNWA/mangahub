<template>
  <div>
    <div class="min-h-screen ">
      <div class="container mx-auto px-4 md:px-6 lg:px-8 max-w-7xl">
        <!-- Breadcrumb -->
        <nav class="mb-8 flex items-center gap-2 text-sm">
          <NuxtLink to="/" class="text-zinc-600 dark:text-zinc-400 hover:text-primary">
            Trang chủ
          </NuxtLink>
          <UIcon name="i-heroicons-chevron-right" class="w-4 h-4 text-zinc-400" />
          <span class="text-zinc-900 dark:text-white">Truyện</span>
        </nav>
        <div class="space-y-10">

          <div class="grid grid-cols-5 gap-4 ">
            <UInput @update:model-value="updateSearch" placeholder="Tìm kiếm" class="w-full"
              icon="i-heroicons-magnifying-glass" size="lg" />
            <SelectMutiData v-model="filters.category_ids" placeholder="Chọn thể loại" url="/categories"
              class="w-full" />
            <SelectData v-model="filters.badge_id" placeholder="Chọn badge" url="/badges" class="w-full" />
            <USelectMenu v-model="filters.status" placeholder="Chọn trạng thái" :items="status" class="w-full"
              value-key="value" label-key="label" />
            <USelectMenu v-model="filters.sort" placeholder="Chọn sắp xếp" value-key="value" label-key="label"
              :items="sorts" class="w-full" />
          </div>
          <div>
            <div v-if="pending">
              <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
                <div v-for="(item, index) in 4" :key="index"
                  class="flex items-center justify-start gap-4 ring-1 ring-gray-200 dark:ring-gray-700 rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                  <div>
                    <USkeleton class="h-24 w-16 rounded-md" />
                  </div>
                  <div class="min-h-24 block w-full">
                    <div class="space-y-1 flex flex-col items-start justify-start w-full h-full">
                      <USkeleton class="h-4 w-[250px]" />
                      <USkeleton class="h-4 w-[200px]" />
                      <div class="line-clamp-1 text-xs">
                        <USkeleton class="h-4 w-[200px]" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else>
              <div v-if="mangas.data.length > 0"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
                <MangaCard v-for="manga in mangas.data" :key="manga.id" :manga="manga" />
              </div>
              <div v-else>
                <div class="flex items-center justify-center h-full">
                  <p class="text-sm text-gray-500">Không tìm thấy truyện nào</p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Manga, Category, Badge } from '~/types'
import { useDebounceFn } from '@vueuse/core'

/* ---------------- ROUTER ---------------- */
const route = useRoute()
const router = useRouter()
/* ---------------- FILTER STATE ---------------- */
const filters = reactive({
  search: '',
  category_ids: [] as string[],
  badge_id: null as string | null,
  status: null as string | null,
  sort: null as string | null,
  page: 1,
  limit: 10,
})

/* ---------------- INIT FROM QUERY ---------------- */
onMounted(() => {
  filters.search = (route.query.search as string) || ''
  filters.status = (route.query.status as string) || null
  filters.sort = (route.query.sort as string) || null

  filters.badge_id = route.query.badge_id
    ? route.query.badge_id.toString()
    : null

  filters.category_ids = route.query.category_ids
    ? (route.query.category_ids as string).split(',').map(String)
    : []
})

/* ---------------- SYNC FILTER -> URL ---------------- */
watch(
  () => ({ ...filters }),
  () => {
    router.replace({
      query: {
        search: filters.search || undefined,
        status: filters.status || undefined,
        sort: filters.sort || undefined,
        badge_id: filters.badge_id || undefined,
        category_ids: filters.category_ids.length
          ? filters.category_ids.join(',')
          : undefined,
        page: filters.page,
      },
    })
  },
  { deep: true }
)

/* ---------------- SEARCH DEBOUNCE ---------------- */
const updateSearch = useDebounceFn((value: string) => {
  filters.search = value
  filters.page = 1
}, 500)

/* ---------------- STATIC DATA ---------------- */
const sorts = [
  { label: 'Mới nhất', value: 'created_at_desc' },
  { label: 'Cũ nhất', value: 'created_at_asc' },
  { label: 'Đánh giá cao', value: 'rating_desc' },
  { label: 'Đánh giá thấp', value: 'rating_asc' },
  { label: 'Lượt theo dõi cao', value: 'follows_desc' },
  { label: 'Lượt theo dõi thấp', value: 'follows_asc' },
  { label: 'A → Z', value: 'name_asc' },
  { label: 'Z → A', value: 'name_desc' },
]

const status = [
  { label: 'Đang ra', value: 'ongoing' },
  { label: 'Hoàn thành', value: 'completed' },
  { label: 'Tạm dừng', value: 'hiatus' },
]


/* ---------------- MANGAS FETCH ---------------- */
const queryParams = computed(() => ({
  search: filters.search,
  category_ids: filters.category_ids.join(','),
  badge_id: filters.badge_id,
  status: filters.status,
  sort: filters.sort,
  page: filters.page,
  limit: filters.limit,
}))

const { data: mangas, pending } = useLazyHttp<{
  ok: boolean
  data: Manga[]
}>('/mangas', {
  query: queryParams,
  watch: [queryParams],
})

/* ---------------- SEO ---------------- */
useHead({
  title: 'Tìm kiếm - WebTruyen',
})
</script>
