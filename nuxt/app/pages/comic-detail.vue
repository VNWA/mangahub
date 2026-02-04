<template>
  <div>


    <!-- Breadcrumb -->
    <nav class="max-w-7xl mx-auto px-4 py-4">
      <ul class="flex gap-2 text-sm">
        <li>
          <NuxtLink to="/" class="text-blue-600 hover:underline">Trang chủ</NuxtLink>
        </li>
        <li class="text-zinc-600">/</li>
        <li>
          <NuxtLink to="/collection" class="text-blue-600 hover:underline">Bộ sưu tập</NuxtLink>
        </li>
        <li class="text-zinc-600">/</li>
        <li class="text-zinc-900 dark:text-white font-semibold">Jujutsu Kaisen</li>
      </ul>
    </nav>

    <!-- Comic Header -->
    <section class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid md:grid-cols-3 gap-8">
          <!-- Thumbnail -->
          <div class="md:col-span-1">
            <Image src="https://via.placeholder.com/250x350" alt="Jujutsu Kaisen thumbnail"
              container-class="w-full rounded-lg shadow-lg" />
            <UButton color="primary" label="Thêm vào yêu thích" icon="i-heroicons-heart" class="mt-4 w-full" block />
          </div>

          <!-- Info -->
          <div class="md:col-span-2">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-2">Jujutsu Kaisen</h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-4">Gege Akutami</p>

            <div class="flex items-center gap-6 mb-6 text-sm">
              <div class="flex items-center gap-2">
                <UIcon name="i-heroicons-star-solid" class="w-5 h-5 text-yellow-500" />
                <div>
                  <p class="font-semibold">9.5/10</p>
                  <p class="text-zinc-600 dark:text-zinc-400">Dựa trên 5.2K đánh giá</p>
                </div>
              </div>
              <div>
                <p class="font-semibold">1.2M lượt xem</p>
                <p class="text-zinc-600 dark:text-zinc-400">Lượt xem</p>
              </div>
              <UBadge label="Đang cập nhật" color="success" size="lg" />
            </div>

            <p class="text-zinc-700 dark:text-zinc-300 mb-6 leading-relaxed">
              Jujutsu Kaisen là một bộ truyện tranh về những người có khả năng phép thuật và cuộc chiến chống lại những
              sinh vật siêu nhiên. Bộ truyện kết hợp hành động, bí ẩn và các yếu tố kỳ ảo tạo nên một câu chuyện hấp
              dẫn.
            </p>

            <div class="mb-6">
              <h4 class="font-semibold text-zinc-900 dark:text-white mb-3">Thể loại:</h4>
              <div class="flex flex-wrap gap-2">
                <UBadge label="Hành động" variant="soft" />
                <UBadge label="Siêu nhiên" variant="soft" />
                <UBadge label="Phiêu lưu" variant="soft" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <UButton color="primary" size="lg" icon="i-heroicons-arrow-right" label="Đọc Chapter Mới Nhất" block />
              <UButton color="neutral" size="lg" icon="i-heroicons-share" label="Chia sẻ" variant="soft" block />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Tabs -->
    <section class="max-w-7xl mx-auto px-4 py-12">
      <UTabs :items="tabs" class="w-full">
        <template #chapters>
          <div class="space-y-2 mt-4">
            <NuxtLink v-for="chapter in chapters" :key="chapter.id" :to="`/comic/1/chapter/${chapter.number}`"
              class="block p-4 bg-white dark:bg-zinc-800 hover:bg-blue-50 dark:hover:bg-zinc-700 rounded-lg transition-colors border border-zinc-200 dark:border-zinc-700">
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="font-semibold text-zinc-900 dark:text-white">{{ chapter.title }}</h4>
                  <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ chapter.date }}</p>
                </div>
                <UIcon name="i-heroicons-arrow-right" class="w-5 h-5 text-zinc-400" />
              </div>
            </NuxtLink>
          </div>
        </template>

        <template #comments>
          <div class="mt-4">
            <CommentSection :comments="comments" user-avatar="https://via.placeholder.com/40x40" user-id="user-123"
              :show-rating="true" />
          </div>
        </template>

        <template #related>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-4">
            <ComicCardVariant v-for="comic in relatedComics" :key="comic.id" :comic="comic" variant="grid" />
          </div>
        </template>
      </UTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
const tabs = [
  { label: 'Chương', slot: 'chapters' },
  { label: 'Bình luận', slot: 'comments' },
  { label: 'Liên quan', slot: 'related' }
]

const chapters = ref([
  { id: '1', number: 250, title: 'Chapter 250: Kết thúc cuộc chiến', date: '2024-01-20' },
  { id: '2', number: 249, title: 'Chapter 249: Bước ngoặt', date: '2024-01-15' },
  { id: '3', number: 248, title: 'Chapter 248: Quyết chiến', date: '2024-01-10' },
  { id: '4', number: 247, title: 'Chapter 247: Chuẩn bị', date: '2024-01-05' }
])

const comments = ref([
  {
    id: '1',
    author: {
      id: 'user-1',
      name: 'Nguyễn Văn A',
      avatar: 'https://via.placeholder.com/40x40',
      verified: true
    },
    content: 'Truyện thực sự tuyệt vời! Cốt truyện rất hấp dẫn và các nhân vật được phát triển rất tốt.',
    rating: 5,
    likes: 234,
    replies: 12,
    createdAt: new Date(Date.now() - 3600000),
    images: []
  },
  {
    id: '2',
    author: {
      id: 'user-2',
      name: 'Trần Thị B',
      avatar: 'https://via.placeholder.com/40x40'
    },
    content: 'Chapter mới này thực sự là một bước ngoặt lớn!',
    rating: 4,
    likes: 156,
    replies: 8,
    createdAt: new Date(Date.now() - 7200000),
    images: []
  }
])

const relatedComics = ref([
  {
    id: '2',
    title: 'Demon Slayer',
    author: 'Koyoharu Gotouge',
    thumbnail: 'https://via.placeholder.com/200x300?text=Demon+Slayer',
    status: 'Hoàn thành',
    rating: 9.4,
    categories: ['Hành động'],
    lastChapter: 'Chapter 205',
    description: 'Cuộc hành trình trả thù'
  },
  {
    id: '3',
    title: 'My Hero Academia',
    author: 'Kohei Horikoshi',
    thumbnail: 'https://via.placeholder.com/200x300?text=My+Hero',
    status: 'Đang cập nhật',
    rating: 9.2,
    categories: ['Hành động'],
    lastChapter: 'Chapter 430',
    description: 'Câu chuyện về siêu anh hùng'
  },
  {
    id: '4',
    title: 'Bleach',
    author: 'Tite Kubo',
    thumbnail: 'https://via.placeholder.com/200x300?text=Bleach',
    status: 'Đang cập nhật',
    rating: 9.3,
    categories: ['Hành động'],
    lastChapter: 'Chapter 686',
    description: 'Cuộc chiến giữa hai thế giới'
  },
  {
    id: '5',
    title: 'Tokyo Ghoul',
    author: 'Sui Ishida',
    thumbnail: 'https://via.placeholder.com/200x300?text=Tokyo+Ghoul',
    status: 'Hoàn thành',
    rating: 9.1,
    categories: ['Bí ẩn'],
    lastChapter: 'Chapter 179',
    description: 'Câu chuyện tối tăm'
  }
])
</script>
