<template>
  <div class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
    <!-- Header -->
    <div class="flex items-start justify-between mb-3">
      <div class="flex items-start gap-3 flex-1">
        <Avatar :src="comment.author.avatar" :alt="comment.author.name" size="sm" />
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <p class="font-semibold text-slate-900 dark:text-white">{{ comment.author.name }}</p>
            <span v-if="comment.author.verified" class="flex-shrink-0">
              <UIcon name="i-heroicons-check-badge" class="w-4 h-4 text-blue-500" />
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(comment.createdAt) }}</p>
        </div>
      </div>
      <UDropdownMenu v-if="isAuthor" :items="dropdownItems" :popper="{ placement: 'bottom-end' }">
        <UButton color="neutral" variant="ghost" icon="i-heroicons-ellipsis-horizontal" size="sm" />
      </UDropdownMenu>
    </div>

    <!-- Rating Stars -->
    <div v-if="comment.rating" class="flex gap-1 mb-3">
      <UIcon v-for="i in 5" :key="i" :name="i <= comment.rating ? 'i-heroicons-star-solid' : 'i-heroicons-star'" :class="[
        'w-4 h-4',
        i <= comment.rating ? 'text-yellow-500' : 'text-gray-300 dark:text-gray-600'
      ]" />
    </div>

    <!-- Content -->
    <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed mb-3">{{ comment.content }}</p>

    <!-- Images -->
    <div v-if="comment.images && comment.images.length > 0" class="grid grid-cols-3 gap-2 mb-3">
      <Image v-for="(img, idx) in comment.images.slice(0, 3)" :key="idx" :src="img" container-class="h-20 rounded"
        hoverable />
      <div v-if="comment.images.length > 3"
        class="h-20 rounded bg-gray-200 dark:bg-slate-700 flex items-center justify-center text-sm font-semibold text-slate-600">
        +{{ comment.images.length - 3 }}
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-4 pt-3 border-t border-slate-200 dark:border-slate-700 text-sm">
      <UButton variant="ghost" color="neutral" icon="i-heroicons-hand-thumb-up" :label="`${comment.likes}`" size="xs" />
      <UButton variant="ghost" color="neutral" icon="i-heroicons-chat-bubble-left" :label="`${comment.replies}`"
        size="xs" />
    </div>

    <!-- Replies Preview -->
    <div v-if="comment.replies > 0 && showReplies"
      class="mt-4 space-y-3 pl-4 border-l-2 border-blue-200 dark:border-blue-900">
      <Comment v-for="reply in comment.replyList" :key="reply.id" :comment="reply" size="sm" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface CommentAuthor {
  id: string
  name: string
  avatar?: string
  verified?: boolean
}

interface Comment {
  id: string
  author: CommentAuthor
  content: string
  rating?: number
  images?: string[]
  likes: number
  replies: number
  replyList?: Comment[]
  createdAt: Date
}

interface Props {
  comment: Comment
  size?: 'sm' | 'md'
  showReplies?: boolean
  isAuthor?: boolean
}

withDefaults(defineProps<Props>(), {
  size: 'md',
  showReplies: false,
  isAuthor: false
})

const dropdownItems = [
  [
    { label: 'Chỉnh sửa', icon: 'i-heroicons-pencil' },
    { label: 'Xóa', icon: 'i-heroicons-trash', color: 'red' }
  ]
]

const formatDate = (date: Date) => {
  const now = new Date()
  const diff = now.getTime() - new Date(date).getTime()
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Vừa xong'
  if (minutes < 60) return `${minutes} phút trước`
  if (hours < 24) return `${hours} giờ trước`
  if (days < 7) return `${days} ngày trước`
  return new Date(date).toLocaleDateString('vi-VN')
}
</script>
