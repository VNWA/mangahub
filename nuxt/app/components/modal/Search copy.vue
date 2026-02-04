<template>
  <UModal>
    <template #content>
      <div class="w-full ">
        <UFieldGroup size="xl" class="w-full py-1">
          <UInput color="neutral" variant="outline" placeholder="Tìm truyện..."
            leading-icon="i-heroicons-magnifying-glass" class="w-full "
            :ui="{ base: 'focus:ring-0 focus-visible:ring-0 ring-0 border-0' }" size="lg" :autofocus="true"
            v-model="searchQuery" @keyup.prevent="loading = true" />

          <UButton @click="close" color="neutral" variant="ghost" icon="i-heroicons-x-mark" />
        </UFieldGroup>
        <div>
          <div v-if="loading">
            <LoadingSkeleton />
          </div>
          <div v-else>
            <UScrollArea v-slot="{ item, index }" :items="items" orientation="vertical" :loading="loading" :virtualize="{
              gap: 16,
              estimateSize: 480
            }" class="w-full h-[60vh] p-4">
              <div>
                <MangaCard :manga="item" variant="horizontal" />
              </div>
            </UScrollArea>
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
