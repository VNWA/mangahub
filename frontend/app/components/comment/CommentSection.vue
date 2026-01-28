<template>
  <div class="space-y-6">
    <!-- Comment Form -->
    <div v-if="auth.logged" class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm">
      <div class="flex gap-3">
        <UAvatar
          :src="auth.user?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${auth.user?.email || 'user'}`"
          :alt="auth.user?.name || 'User'"
          size="sm"
        />
        <div class="flex-1">
          <UTextarea
            v-model="newComment"
            placeholder="Viết bình luận..."
            :rows="3"
            :disabled="submitting"
            class="mb-3"
          />
          <div class="flex justify-end gap-2">
            <UButton
              @click="cancelComment"
              variant="ghost"
              color="neutral"
              size="sm"
              :disabled="submitting"
            >
              Hủy
            </UButton>
            <UButton
              @click="submitComment"
              color="primary"
              size="sm"
              :loading="submitting"
              :disabled="!newComment.trim()"
            >
              Bình luận
            </UButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Login Prompt -->
    <div v-else class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 text-center">
      <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
        Đăng nhập để bình luận
      </p>
      <UButton to="/auth/login" color="primary" size="sm">
        Đăng nhập
      </UButton>
    </div>

    <!-- Comments List -->
    <div class="space-y-4">
      <div v-if="loading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="animate-pulse">
          <div class="bg-slate-200 dark:bg-slate-700 rounded-lg h-24"></div>
        </div>
      </div>

      <div v-else-if="comments.length === 0" class="text-center py-8">
        <UIcon name="i-heroicons-chat-bubble-left-right" class="w-12 h-12 text-slate-400 mx-auto mb-3" />
        <p class="text-slate-600 dark:text-slate-400">Chưa có bình luận nào. Hãy là người đầu tiên!</p>
      </div>

      <CommentItem
        v-for="comment in comments"
        :key="comment.id"
        :comment="comment"
        :commentable-type="commentableType"
        :commentable-id="commentableId"
        @reply="handleReply"
        @edit="handleEdit"
        @delete="handleDelete"
        @react="handleReact"
        @load-replies="loadReplies"
      />
    </div>

    <!-- Load More -->
    <div v-if="hasMore && !loading" class="text-center">
      <UButton
        @click="loadMore"
        variant="ghost"
        color="primary"
        :loading="loadingMore"
      >
        Xem thêm bình luận
      </UButton>
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
  commentableType: 'Manga' | 'MangaChapter'
  commentableId: number
}

const props = defineProps<Props>()
const auth = useAuthStore()
const toast = useToast()

const comments = ref<Comment[]>([])
const newComment = ref('')
const submitting = ref(false)
const loading = ref(true)
const loadingMore = ref(false)
const currentPage = ref(1)
const hasMore = ref(false)
const pageId = ref<number | null>(null)

// Load comments
const loadComments = async (page = 1, append = false) => {
  try {
    if (page === 1) {
      loading.value = true
    } else {
      loadingMore.value = true
    }

    const data = await $http<{
      ok: boolean
      data: Comment[]
      pagination: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
    }>('/comments', {
      query: {
        commentable_type: props.commentableType,
        commentable_id: props.commentableId,
        page,
        per_page: 20
      }
    })

    if (data?.ok && data.data) {
      if (append) {
        comments.value = [...comments.value, ...data.data]
      } else {
        comments.value = data.data
      }
      if (data.page_id) {
        pageId.value = data.page_id
      }
      currentPage.value = data.pagination.current_page
      hasMore.value = data.pagination.current_page < data.pagination.last_page
    }
  } catch (error) {
    console.error('Failed to load comments:', error)
    toast.add({
      title: 'Lỗi',
      description: 'Không thể tải bình luận',
      color: 'error'
    })
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

// Submit comment
const submitComment = async () => {
  if (!newComment.value.trim() || !auth.logged) return

  submitting.value = true
  try {
    const data = await $http<{
      ok: boolean
      data: Comment
    }>('/comments', {
      method: 'POST',
      body: {
        commentable_type: props.commentableType,
        commentable_id: props.commentableId,
        content: newComment.value.trim()
      }
    })

    if (data?.ok && data.data) {
      comments.value = [data.data, ...comments.value]
      newComment.value = ''
      toast.add({
        title: 'Thành công',
        description: 'Đã thêm bình luận',
        color: 'success'
      })
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || 'Không thể thêm bình luận',
      color: 'error'
    })
  } finally {
    submitting.value = false
  }
}

const cancelComment = () => {
  newComment.value = ''
}

// Handle reply
const handleReply = async (parentId: number, content: string) => {
  try {
    const data = await $http<{
      ok: boolean
      data: Comment
    }>('/comments', {
      method: 'POST',
      body: {
        commentable_type: props.commentableType,
        commentable_id: props.commentableId,
        parent_id: parentId,
        content
      }
    })

    if (data?.ok && data.data) {
      // Find parent and add reply
      const findAndAddReply = (commentList: Comment[]): boolean => {
        for (const comment of commentList) {
          if (comment.id === parentId) {
            if (!comment.replies) {
              comment.replies = []
            }
            comment.replies.push(data.data)
            comment.replies_count++
            return true
          }
          if (comment.replies && findAndAddReply(comment.replies)) {
            return true
          }
        }
        return false
      }
      findAndAddReply(comments.value)

      toast.add({
        title: 'Thành công',
        description: 'Đã thêm phản hồi',
        color: 'success'
      })
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || 'Không thể thêm phản hồi',
      color: 'error'
    })
  }
}

// Handle edit
const handleEdit = async (commentId: number, content: string) => {
  try {
    const data = await $http<{
      ok: boolean
      data: Comment
    }>(`/comments/${commentId}`, {
      method: 'PUT',
      body: {
        content
      }
    })

    if (data?.ok && data.data) {
      // Update comment in list
      const updateComment = (commentList: Comment[]): boolean => {
        for (const comment of commentList) {
          if (comment.id === commentId) {
            Object.assign(comment, data.data)
            return true
          }
          if (comment.replies && updateComment(comment.replies)) {
            return true
          }
        }
        return false
      }
      updateComment(comments.value)

      toast.add({
        title: 'Thành công',
        description: 'Đã cập nhật bình luận',
        color: 'success'
      })
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || 'Không thể cập nhật bình luận',
      color: 'error'
    })
  }
}

// Handle delete
const handleDelete = async (commentId: number) => {
  try {
    const data = await $http<{
      ok: boolean
    }>(`/comments/${commentId}`, {
      method: 'DELETE'
    })

    if (data?.ok) {
      // Remove comment from list
      const removeComment = (commentList: Comment[]): Comment[] => {
        return commentList.filter(comment => {
          if (comment.id === commentId) {
            return false
          }
          if (comment.replies) {
            comment.replies = removeComment(comment.replies)
            comment.replies_count = comment.replies.length
          }
          return true
        })
      }
      comments.value = removeComment(comments.value)

      toast.add({
        title: 'Thành công',
        description: 'Đã xóa bình luận',
        color: 'success'
      })
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || 'Không thể xóa bình luận',
      color: 'error'
    })
  }
}

// Handle react
const handleReact = async (commentId: number, type: 'like' | 'dislike') => {
  try {
    const data = await $http<{
      ok: boolean
      data: {
        likes_count: number
        dislikes_count: number
        user_reaction: 'like' | 'dislike' | null
      }
    }>(`/comments/${commentId}/react`, {
      method: 'POST',
      body: { type }
    })

    if (data?.ok && data.data) {
      // Update comment reaction
      const updateReaction = (commentList: Comment[]): boolean => {
        for (const comment of commentList) {
          if (comment.id === commentId) {
            comment.likes_count = data.data.likes_count
            comment.dislikes_count = data.data.dislikes_count
            comment.user_reaction = data.data.user_reaction
            return true
          }
          if (comment.replies && updateReaction(comment.replies)) {
            return true
          }
        }
        return false
      }
      updateReaction(comments.value)
    }
  } catch (error: any) {
    if (error.response?.status !== 401) {
      toast.add({
        title: 'Lỗi',
        description: 'Không thể cập nhật reaction',
        color: 'error'
      })
    }
  }
}

// Load replies
const loadReplies = async (commentId: number) => {
  try {
    const data = await $http<{
      ok: boolean
      data: Comment[]
    }>('/comments', {
      query: {
        commentable_type: props.commentableType,
        commentable_id: props.commentableId,
        parent_id: commentId
      }
    })

    if (data?.ok && data.data) {
      const updateReplies = (commentList: Comment[]): boolean => {
        for (const comment of commentList) {
          if (comment.id === commentId) {
            // Merge with existing replies if any, avoiding duplicates
            if (comment.replies && comment.replies.length > 0) {
              const existingIds = new Set(comment.replies.map((r: Comment) => r.id))
              const newReplies = data.data.filter((r: Comment) => !existingIds.has(r.id))
              comment.replies = [...comment.replies, ...newReplies]
            } else {
              comment.replies = data.data
            }
            return true
          }
          if (comment.replies && updateReplies(comment.replies)) {
            return true
          }
        }
        return false
      }
      updateReplies(comments.value)
    }
  } catch (error) {
    console.error('Failed to load replies:', error)
  }
}

// Load more
const loadMore = () => {
  if (hasMore.value && !loadingMore.value) {
    loadComments(currentPage.value + 1, true)
  }
}

// Realtime events (client-side only)
let echoChannel: any = null

onMounted(() => {
  loadComments()
  
  // Setup realtime only on client-side
  if (typeof window !== 'undefined') {
    watch(pageId, (newPageId) => {
      if (newPageId) {
        const echo = useEcho()
        if (echo) {
          // Leave previous channel if exists
          if (echoChannel) {
            echo.leave(echoChannel)
          }
          
          // Join new channel
          echoChannel = `page.${newPageId}`
          echo.channel(echoChannel)
            .listen('.comment.created', (data: any) => {
              // Only add top-level comments (no parent_id)
              if (data.comment && !data.comment.parent_id && !comments.value.find(c => c.id === data.comment.id)) {
                comments.value = [data.comment, ...comments.value]
              }
            })
            .listen('.comment.replied', (data: any) => {
              // Add reply to the correct parent in nested structure
              // Event sends 'reply' key, not 'comment'
              if (data.reply && data.reply.parent_id) {
                const reply: Comment = {
                  id: data.reply.id,
                  content: data.reply.content,
                  likes_count: 0,
                  dislikes_count: 0,
                  replies_count: 0,
                  is_edited: false,
                  is_pinned: false,
                  root_id: data.reply.root_id || null,
                  depth: data.reply.depth || 1,
                  parent_id: data.reply.parent_id,
                  user_reaction: null,
                  user: data.reply.user,
                  created_at: data.reply.created_at,
                  updated_at: data.reply.created_at,
                  is_owner: data.reply.user?.id === auth.user?.id
                }
                
                const findAndAddReply = (commentList: Comment[]): boolean => {
                  for (const comment of commentList) {
                    if (comment.id === data.reply.parent_id) {
                      if (!comment.replies) {
                        comment.replies = []
                      }
                      // Check if reply already exists
                      if (!comment.replies.find((r: Comment) => r.id === reply.id)) {
                        comment.replies.push(reply)
                        comment.replies_count++
                      }
                      return true
                    }
                    if (comment.replies && findAndAddReply(comment.replies)) {
                      return true
                    }
                  }
                  return false
                }
                findAndAddReply(comments.value)
              }
            })
        }
      }
    }, { immediate: true })
  }
})

// Watch for auth changes
watch(() => auth.logged, () => {
  if (auth.logged) {
    loadComments()
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    const echo = useEcho()
    if (echo && echoChannel) {
      echo.leave(echoChannel)
    }
  }
})
</script>
