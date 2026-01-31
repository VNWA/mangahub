<template>
  <div>
    <!-- Breadcrumb -->
    <nav class="max-w-7xl mx-auto px-4 py-4">
      <ul class="flex items-center gap-2 text-sm">
        <li>
          <NuxtLink to="/" class="text-primary hover:underline">Trang chủ</NuxtLink>
        </li>
        <li class="text-slate-400">/</li>
        <li class="text-slate-900 dark:text-white font-semibold">{{ comicTitle }}</li>
      </ul>
    </nav>

    <!-- Comic Header -->
    <section class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
      <div class="max-w-7xl mx-auto px-4 py-6 md:py-8">
        <div class="grid md:grid-cols-3 gap-6 md:gap-8">
          <!-- Thumbnail -->
          <div class="md:col-span-1">
            <img
              src="https://via.placeholder.com/250x350"
              alt="Thumbnail"
              class="w-full rounded-lg shadow-lg"
            />
          </div>

          <!-- Info -->
          <div class="md:col-span-2">
            <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-2">{{ comicTitle }}</h1>
            <p class="text-lg text-slate-600 dark:text-slate-400 mb-4">{{ comicAuthor }}</p>

            <div class="flex items-center gap-4 mb-6">
              <div class="flex items-center gap-2">
                <UIcon name="i-heroicons-star" class="w-5 h-5 text-yellow-500" />
                <span class="font-semibold">9.5/10</span>
              </div>
              <UBadge label="Đang cập nhật" color="success" />
            </div>

            <p class="text-slate-700 dark:text-slate-300 mb-6">
              Mô tả truyện: Đây là một bộ truyện tranh tuyệt vời với nội dung hấp dẫn, nhân vật đa dạng và cốt truyện thú vị. Hãy đọc ngay!
            </p>

            <div class="mb-6">
              <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Thể loại:</h4>
              <div class="flex flex-wrap gap-2">
                <UBadge label="Hành động" variant="soft" />
                <UBadge label="Siêu nhiên" variant="soft" />
                <UBadge label="Phiêu lưu" variant="soft" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <UButton
                color="primary"
                size="lg"
                icon="i-heroicons-arrow-right"
                label="Đọc Chapter Mới Nhất"
                block
              />
              <UButton
                color="neutral"
                size="lg"
                icon="i-heroicons-heart"
                variant="soft"
                label="Yêu thích"
                block
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Chapters List -->
    <section class="max-w-7xl mx-auto px-4 py-12">
      <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-6">Danh sách Chapter</h2>
      
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <div
          v-for="(chapter, index) in chapters"
          :key="chapter.id"
          class="border-b border-slate-200 dark:border-slate-700 last:border-b-0"
        >
          <NuxtLink
            :to="`/comic/${comicId}/chapter/${chapter.number}`"
            class="block px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            <div class="flex justify-between items-center">
              <div>
                <h4 class="font-semibold text-slate-900 dark:text-white">{{ chapter.title }}</h4>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ chapter.date }}</p>
              </div>
              <UIcon name="i-heroicons-arrow-right" class="w-5 h-5 text-slate-400" />
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const comicId = route.params.id as string
const comicTitle = 'Jujutsu Kaisen'
const comicAuthor = 'Gege Akutami'

useHead({
  title: `${comicTitle} - Đọc Truyện Online - WebTruyện`,
  meta: [
    { name: 'description', content: `Đọc ${comicTitle} online. Tác giả: ${comicAuthor}. Cập nhật hàng ngày` },
    { name: 'robots', content: 'index, follow' },
    { property: 'og:type', content: 'article' },
    { property: 'og:title', content: comicTitle },
    { property: 'og:image', content: 'https://via.placeholder.com/1200x630' }
  ]
})

const chapters = ref([
  { id: '1', number: 250, title: 'Chapter 250: Kết thúc cuộc chiến', date: '2024-01-20' },
  { id: '2', number: 249, title: 'Chapter 249: Bước ngoặt', date: '2024-01-15' },
  { id: '3', number: 248, title: 'Chapter 248: Quyết chiến', date: '2024-01-10' },
  { id: '4', number: 247, title: 'Chapter 247: Chuẩn bị', date: '2024-01-05' },
  { id: '5', number: 246, title: 'Chapter 246: Cả bầy', date: '2023-12-30' }
])
</script>
