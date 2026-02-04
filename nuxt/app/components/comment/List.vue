<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Bình luận</h3>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ comments.length }} bình luận</p>
      </div>
      <UButton icon="i-heroicons-arrow-up-down" color="neutral" variant="ghost" size="sm" />
    </div>

    <!-- Comment Form -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-4">
      <div class="flex gap-3 mb-3">
        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=CurrentUser" alt="Avatar"
          class="w-10 h-10 rounded-full flex-shrink-0" />
        <div class="flex-1">
          <textarea v-model="newComment" placeholder="Chia sẻ cảm nghĩ của bạn..."
            class="w-full bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg p-3 placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
            rows="3"></textarea>
        </div>
      </div>
      <div class="flex items-center justify-end gap-2">
        <UButton color="neutral" variant="ghost" label="Hủy" size="sm" />
        <UButton color="primary" label="Bình luận" @click="submitComment" size="sm" />
      </div>
    </div>

    <!-- Comments -->
    <div v-if="comments.length > 0" class="space-y-3">
      <div v-for="comment in comments" :key="comment.id" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-4">
        <!-- Comment Header -->
        <div class="flex gap-3 mb-3">
          <img :src="comment.avatar" :alt="comment.author" class="w-8 h-8 rounded-full flex-shrink-0" />
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <p class="font-semibold text-zinc-900 dark:text-white">{{ comment.author }}</p>
              <span class="text-xs text-zinc-500">{{ formatDate(comment.createdAt) }}</span>
            </div>

            <!-- Rating -->
            <div v-if="comment.rating" class="flex gap-1 my-1">
              <UIcon v-for="i in 5" :key="i" :name="i <= comment.rating ? 'i-heroicons-star-solid' : 'i-heroicons-star'"
                :class="[
                  'w-4 h-4',
                  i <= comment.rating ? 'text-yellow-400' : 'text-zinc-300'
                ]" />
            </div>
          </div>

          <!-- Actions -->
          <UDropdownMenu :items="commentActions" :popper="{ placement: 'bottom-end' }">
            <UButton icon="i-heroicons-ellipsis-horizontal" color="neutral" variant="ghost" size="sm" square />
          </UDropdownMenu>
        </div>

        <!-- Content -->
        <p class="text-zinc-700 dark:text-zinc-300 text-sm mb-3">{{ comment.content }}</p>

        <!-- Actions -->
        <div class="flex gap-4 text-xs text-zinc-600 dark:text-zinc-400">
          <button class="hover:text-primary transition-colors flex items-center gap-1">
            <UIcon name="i-heroicons-hand-thumb-up" class="w-4 h-4" />
            <span>{{ comment.likes }}</span>
          </button>
          <button class="hover:text-primary transition-colors flex items-center gap-1">
            <UIcon name="i-heroicons-chat-bubble-left" class="w-4 h-4" />
            <span>Trả lời</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <UIcon name="i-heroicons-chat-bubble-left" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
      <p class="text-zinc-600 dark:text-zinc-400">Chưa có bình luận nào. Hãy là người đầu tiên!</p>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Comment {
  id: string
  author: string
  avatar: string
  content: string
  rating?: number
  likes: number
  createdAt: string
}

interface Props {
  comments: Comment[]
}

defineProps<Props>()

const newComment = ref('')

const commentActions = [
  [
    { label: 'Báo cáo', icon: 'i-heroicons-flag' },
  ]
]

const formatDate = (date: string) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (hours < 1) return 'Vừa xong'
  if (hours < 24) return `${hours}h trước`
  if (days < 7) return `${days}d trước`
  return d.toLocaleDateString('vi-VN')
}

const submitComment = () => {
  if (newComment.value.trim()) {
    console.log('Comment submitted:', newComment.value)
    newComment.value = ''
  }
}
</script>
