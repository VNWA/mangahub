<template>
  <UModal>
    <template #content>
      <div class="w-full ">
        <UFieldGroup size="xl" class="w-full py-1">
          <UInput color="neutral" variant="outline" placeholder="Tìm truyện..."
            leading-icon="i-heroicons-magnifying-glass" class="w-full "
            :ui="{ base: 'focus:ring-0 focus-visible:ring-0 ring-0 border-0 ' }" size="lg" :autofocus="true"
            v-model="searchQuery" @keyup.prevent="loading = true" />

          <UButton @click="close" color="neutral" variant="ghost" icon="i-heroicons-x-mark" />
        </UFieldGroup>
        <div class="min-h-[60vh]">
          <div v-if="loading">
            <div class="flex flex-col gap-4 p-4">
              <div v-for="(item, index) in 3" :key="index"
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
            <div v-if="items.length > 0">
              <UScrollArea v-slot="{ item, index }" :items="items" orientation="vertical" :loading="loading"
                :virtualize="{
                  gap: 16,
                  estimateSize: 480
                }" class="w-full h-[60vh] p-4">
                <NuxtLink :to="`/${item.slug}`" aria-label="Manga Link" @click="close">
                  <div
                    class="flex items-center justify-start gap-4 ring-1 ring-gray-200 dark:ring-gray-700 rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <div>
                      <Image :src="item.avatar" alt="Manga Avatar" width="85" height="120" class="h-24 rounded-md" />

                    </div>
                    <div class="min-h-24 block w-full">
                      <div class="space-y-1 flex flex-col items-start justify-start w-full h-full">
                        <h3 class=" text-md font-bold">{{ item.name }}</h3>
                        <p class="text-sm text-gray-500">{{ item.author?.name }}</p>
                        <div class="line-clamp-1 text-xs">
                          {{ item.description }}
                        </div>
                      </div>
                    </div>
                  </div>
                </NuxtLink>
              </UScrollArea>
            </div>
            <div v-else>
              <div class="flex items-center justify-center h-full">
                <p class="text-sm text-gray-500">Không tìm thấy truyện nào</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </UModal>
</template>

<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core'
import type { Manga } from '~/types'




const items = ref<Manga[]>([])
const searchQuery = ref('')
const loading = ref(false)
const search = useDebounceFn(async () => {
  await $http('/search', {
    method: 'GET',
    onFetch() {
      loading.value = true
    },
    query: {
      q: searchQuery.value,
      page: 1,
      per_page: 10
    },
    onResponse({ response }) {
      if (response.status === 200) {
        loading.value = false
        items.value = response._data.data
      }
    }
  })



}, 500) // 500ms sau khi ngừng gõ

watch(searchQuery, () => {
  search()
})

const emit = defineEmits(['close'])

const close = () => {
  emit('close')
}


</script>
