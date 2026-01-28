<template>
  <div class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm">
    <!-- Main Comment -->
    <div class="flex gap-3">
      <UAvatar
        :src="comment.user?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${comment.user?.email || 'user'}`"
        :alt="comment.user?.name || 'User'" size="sm" />

      <div class="flex-1 min-w-0">
        <!-- Header -->
        <div class="flex items-start justify-between mb-2">
          <div class="flex items-center gap-2">
            <span class="font-semibold text-slate-900 dark:text-white">
              {{ comment.user?.name || 'Anonymous' }}
            </span>
            <UBadge v-if="comment.is_pinned" color="primary" variant="soft" size="xs">
              Ghim
            </UBadge>
            <span v-if="comment.is_edited" class="text-xs text-slate-500">
              (đã chỉnh sửa)
            </span>
          </div>
          <span class="text-xs text-slate-500">
            {{ formatTime(comment.created_at) }}
          </span>
        </div>

        <!-- Content -->
        <div class="text-slate-700 dark:text-slate-300 mb-3 whitespace-pre-wrap break-words">
          {{ comment.content }}
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-4">
          <!-- Like -->
          <button @click="$emit('react', comment.id, 'like')" :class="[
            'flex items-center gap-1 text-sm transition-colors',
            comment.user_reaction === 'like'
              ? 'text-blue-600 dark:text-blue-400 font-semibold'
              : 'text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400'
          ]">
            <UIcon
              :name="comment.user_reaction === 'like' ? 'i-heroicons-hand-thumb-up-solid' : 'i-heroicons-hand-thumb-up'"
              class="w-4 h-4" />
            <span>{{ comment.likes_count || 0 }}</span>
          </button>

          <!-- Dislike -->
          <button @click="$emit('react', comment.id, 'dislike')" :class="[
            'flex items-center gap-1 text-sm transition-colors',
            comment.user_reaction === 'dislike'
              ? 'text-red-600 dark:text-red-400 font-semibold'
              : 'text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400'
          ]">
            <UIcon
              :name="comment.user_reaction === 'dislike' ? 'i-heroicons-hand-thumb-down-solid' : 'i-heroicons-hand-thumb-down'"
              class="w-4 h-4" />
            <span>{{ comment.dislikes_count || 0 }}</span>
          </button>

          <!-- Reply -->
          <button @click="showReplyForm = !showReplyForm"
            class="flex items-center gap-1 text-sm text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <UIcon name="i-heroicons-arrow-turn-down-left" class="w-4 h-4" />
            <span>Phản hồi</span>
            <span v-if="comment.replies_count > 0" class="text-xs">
              ({{ comment.replies_count }})
            </span>
          </button>

          <!-- Edit/Delete (Owner only) -->
          <template v-if="comment.is_owner">
            <button @click="showEditForm = !showEditForm"
              class="text-sm text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
              Sửa
            </button>
            <button @click="confirmDelete"
              class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">
              Xóa
            </button>
          </template>
        </div>

        <!-- Reply Form -->
        <div v-if="showReplyForm" class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
          <div class="flex gap-3">
            <UAvatar
              :src="auth.user?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${auth.user?.email || 'user'}`"
              :alt="auth.user?.name || 'User'" size="sm" />
            <div class="flex-1 w-full">
              <UTextarea v-model="replyContent" placeholder="Viết phản hồi..." :rows="3" :ui="{
                'base': 'w-full',
              }" class="mb-2 w-full" :autofocus="true" />
              <div class="flex justify-end gap-2">
                <UButton @click="showReplyForm = false; replyContent = ''" variant="ghost" size="sm">
                  Hủy
                </UButton>
                <UButton @click="submitReply" color="primary" size="sm" :disabled="!replyContent.trim()">
                  Phản hồi
                </UButton>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Form -->
        <div v-if="showEditForm" class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
          <UTextarea v-model="editContent" :rows="3" class="mb-2" />
          <div class="flex justify-end gap-2">
            <UButton @click="showEditForm = false; editContent = comment.content" variant="ghost" size="sm">
              Hủy
            </UButton>
            <UButton @click="submitEdit" color="primary" size="sm"
              :disabled="!editContent.trim() || editContent === comment.content">
              Lưu
            </UButton>
          </div>
        </div>

        <!-- Replies -->
        <div v-if="comment.replies && comment.replies.length > 0" :class="[
          'mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 space-y-3',
          comment.depth > 0 ? 'ml-4 pl-4 border-l-2 border-slate-200 dark:border-slate-700' : ''
        ]">
          <CommentItem v-for="reply in comment.replies" :key="reply.id" :comment="reply"
            :commentable-type="commentableType" :commentable-id="commentableId" :is-reply="true"
            @reply="(id, content) => $emit('reply', id, content)" @edit="(id, content) => $emit('edit', id, content)"
            @delete="(id) => $emit('delete', id)" @react="(id, type) => $emit('react', id, type)"
            @loadReplies="(id) => $emit('loadReplies', id)" />
        </div>

        <!-- Load Replies Button -->
        <div v-else-if="comment.replies_count > 0 && !comment.replies"
          class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
          <UButton @click="handleLoadReplies" variant="ghost" color="primary" size="sm" :loading="loadingReplies">
            Xem {{ comment.replies_count }} phản hồi
          </UButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Comment {
  id: number
  content: string
  likes_count: number
  dislikes_count: number
  replies_count: number
  is_edited: boolean
  is_pinned: boolean
  root_id: number | null
  depth: number
  parent_id: number | null
  user_reaction: 'like' | 'dislike' | null
  user: {
    id: number
    name: string
    email: string
    avatar: string | null
  } | null
  replies?: Comment[]
  created_at: string
  updated_at: string
  is_owner: boolean
}

interface Props {
  comment: Comment
  commentableType: 'Manga' | 'MangaChapter'
  commentableId: number
  isReply?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isReply: false
})

const emit = defineEmits<{
  reply: [commentId: number, content: string]
  edit: [commentId: number, content: string]
  delete: [commentId: number]
  react: [commentId: number, type: 'like' | 'dislike']
  loadReplies: [commentId: number]
}>()

const auth = useAuthStore()
const toast = useToast()
const showReplyForm = ref(false)
const showEditForm = ref(false)
const replyContent = ref('')
const editContent = ref(props.comment.content)
const loadingReplies = ref(false)

const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const seconds = Math.floor(diff / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (days > 7) {
    return date.toLocaleDateString('vi-VN')
  } else if (days > 0) {
    return `${days} ngày trước`
  } else if (hours > 0) {
    return `${hours} giờ trước`
  } else if (minutes > 0) {
    return `${minutes} phút trước`
  } else {
    return 'Vừa xong'
  }
}

const submitReply = () => {
  if (!replyContent.value.trim()) return
  emit('reply', props.comment.id, replyContent.value.trim())
  replyContent.value = ''
  showReplyForm.value = false
}

const submitEdit = () => {
  if (!editContent.value.trim() || editContent.value === props.comment.content) return
  emit('edit', props.comment.id, editContent.value.trim())
  showEditForm.value = false
}

const confirmDelete = () => {
  if (confirm('Bạn có chắc muốn xóa bình luận này?')) {
    emit('delete', props.comment.id)
  }
}

const handleLoadReplies = async () => {
  if (loadingReplies.value || props.comment.replies) return

  loadingReplies.value = true
  emit('loadReplies', props.comment.id)
  // Wait a bit for the parent to load replies
  await new Promise(resolve => setTimeout(resolve, 500))
  loadingReplies.value = false
}
</script>
