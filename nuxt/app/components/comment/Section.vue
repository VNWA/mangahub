<template>
  <div class="space-y-6">
    <!-- Comment Form -->
    <div v-if="auth.logged" class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm">
      <div class="flex gap-3">
        <UAvatar
          :src="auth.user?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${auth.user?.email || 'user'}`"
          :alt="auth.user?.name || 'User'" size="sm" />
        <div class="flex-1">
          <UTextarea v-model="newComment" placeholder="Viết bình luận..." :rows="3" :disabled="submitting"
            class="mb-3" />
          <div class="flex justify-end gap-2">
            <UButton @click="cancelComment" variant="ghost" color="neutral" size="sm" :disabled="submitting">
              Hủy
            </UButton>
            <UButton @click="submitComment" color="primary" size="sm" :loading="submitting"
              :disabled="!newComment.trim()">
              Bình luận
            </UButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Login Prompt -->
    <div v-else class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-center">
      <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
        Đăng nhập để bình luận
      </p>
      <UButton to="/auth/login" color="primary" size="sm">
        Đăng nhập
      </UButton>
    </div>

    <!-- Comments List (Thread-based) -->
    <div class="space-y-6">
      <div v-if="loading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="animate-pulse">
          <div class="bg-zinc-200 dark:bg-zinc-700 rounded-lg h-24"></div>
        </div>
      </div>

      <div v-else-if="threads.length === 0" class="text-center py-8">
        <UIcon name="i-heroicons-chat-bubble-left-right" class="w-12 h-12 text-zinc-400 mx-auto mb-3" />
        <p class="text-zinc-600 dark:text-zinc-400">Chưa có bình luận nào. Hãy là người đầu tiên!</p>
      </div>

      <!-- Thread (Root comment + replies) -->
      <div v-for="thread in threads" :key="thread.root.id"
        class="space-y-3 border-b border-zinc-200 dark:border-zinc-700 pb-6 last:border-b-0">
        <!-- Root Comment -->
        <div>
          <CommentItem :comment="thread.root" :commentable-type="commentableType" :commentable-id="commentableId"
            @reply="handleReply" @edit="handleEdit" @delete="handleDelete" @react="handleReact" />
          <!-- Hiển thị số lượng replies -->
          <div v-if="thread.total_replies > 0" class="ml-8 mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            {{ thread.total_replies }} {{ thread.total_replies === 1 ? 'phản hồi' : 'phản hồi' }}
          </div>
        </div>

        <!-- Replies - Flat list, hiển thị rõ reply của comment nào -->
        <div v-if="thread.replies && thread.replies.length > 0"
          class="ml-8 pl-4 border-l-2 border-zinc-200 dark:border-zinc-700 space-y-3">
          <CommentItem v-for="reply in thread.replies" :key="reply.id" :comment="reply"
            :commentable-type="commentableType" :commentable-id="commentableId" :is-reply="true" @reply="handleReply"
            @edit="handleEdit" @delete="handleDelete" @react="handleReact" />
        </div>

        <!-- Load More Replies for this thread -->
        <div v-if="thread.total_replies > thread.loaded_replies" class="ml-8 pl-4 pt-2">
          <UButton @click="loadThreadReplies(thread.root.id)" variant="ghost" color="primary" size="sm"
            :loading="loadingThreadReplies[thread.root.id]">
            <UIcon name="i-heroicons-arrow-down" class="w-4 h-4 mr-1" />
            Xem thêm {{ thread.total_replies - thread.loaded_replies }} phản hồi
          </UButton>
        </div>
      </div>
    </div>

    <!-- Load More Root Comments -->
    <div v-if="hasMore && !loading" class="text-center mt-6">
      <UButton @click="loadMore" variant="ghost" color="primary" :loading="loadingMore">
        <UIcon name="i-heroicons-arrow-down" class="w-4 h-4 mr-1" />
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
  parent_user?: {
    id: number
    name: string
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

interface Thread {
  root: Comment
  replies: Comment[]
  total_replies: number
  loaded_replies: number
}

const threads = ref<Thread[]>([])
const newComment = ref('')
const submitting = ref(false)
const loading = ref(true)
const loadingMore = ref(false)
const loadingThreadReplies = ref<Record<number, boolean>>({})
const currentPage = ref(1)
const hasMore = ref(false)
const pageId = ref<number | null>(null)

// Load comments (threads) - 10 root comments mỗi lần
const loadComments = async (page = 1, append = false) => {
  // Don't load if commentableId is not valid
  if (!props.commentableId || props.commentableId <= 0) {
    loading.value = false
    return
  }

  try {
    if (page === 1) {
      loading.value = true
    } else {
      loadingMore.value = true
    }

    const data = await $http<{
      ok: boolean
      data: Thread[]
      pagination: {
        current_page: number
        last_page: number
        per_page: number
        total: number
      }
      page_id?: number
    }>('/comments', {
      query: {
        commentable_type: props.commentableType,
        commentable_id: props.commentableId,
        page,
        per_page: 10 // Load 10 root comments mỗi lần
      }
    })

    if (data?.ok && data.data) {
      if (append) {
        threads.value = [...threads.value, ...data.data]
      } else {
        threads.value = data.data
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

// Load more replies for a specific thread
const loadThreadReplies = async (rootId: number) => {
  if (loadingThreadReplies.value[rootId]) return

  loadingThreadReplies.value[rootId] = true
  try {
    const thread = threads.value.find(t => t.root.id === rootId)
    if (!thread) return

    // Tính page dựa trên số replies đã load
    // Ban đầu: 3 replies (offset 0-2)
    // Load more lần 1: cần load từ offset 3, load 10 replies (offset 3-12)
    // Load more lần 2: cần load từ offset 13, load 10 replies (offset 13-22)
    const currentLoaded = thread.loaded_replies
    let page = 1
    let perPage = 10

    if (currentLoaded === 3) {
      // Lần đầu load more: load từ offset 3, cần 10 replies
      // Dùng page 1 với per_page = 13 để lấy items 1-13, rồi bỏ 3 đầu
      perPage = 13
      page = 1
    } else if (currentLoaded > 3) {
      // Các lần sau: tính page dựa trên offset
      // Offset = currentLoaded, per_page = 10
      // Page = Math.ceil((currentLoaded + 1) / 10) nhưng Laravel pagination dùng offset = (page-1) * per_page
      // Nên: page = Math.floor(currentLoaded / 10) + 1
      page = Math.floor(currentLoaded / 10) + 1
      perPage = 10
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
        root_id: rootId,
        page,
        per_page: perPage
      }
    })

    if (data?.ok && data.data) {
      if (currentLoaded === 3) {
        // Lần đầu load more: bỏ 3 replies đầu, lấy phần còn lại (items 4-13)
        const newReplies = data.data.slice(3)
        thread.replies = [...thread.replies, ...newReplies]
      } else {
        // Các lần sau: merge bình thường, filter duplicates
        const existingIds = new Set(thread.replies.map(r => r.id))
        const newReplies = data.data.filter(r => !existingIds.has(r.id))
        thread.replies = [...thread.replies, ...newReplies]
      }
      // Sort lại theo created_at để đảm bảo thứ tự
      thread.replies.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
      thread.loaded_replies = thread.replies.length
    }
  } catch (error) {
    console.error('Failed to load thread replies:', error)
    toast.add({
      title: 'Lỗi',
      description: 'Không thể tải thêm phản hồi',
      color: 'error'
    })
  } finally {
    loadingThreadReplies.value[rootId] = false
  }
}

// Submit comment
const submitComment = async () => {
  if (!newComment.value.trim() || !auth.logged || !props.commentableId || props.commentableId <= 0) return

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
      // Add new thread at the beginning
      const newThread: Thread = {
        root: data.data,
        replies: [],
        total_replies: 0,
        loaded_replies: 0
      }
      threads.value = [newThread, ...threads.value]
      newComment.value = ''
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data

  } finally {
    submitting.value = false
  }
}

const cancelComment = () => {
  newComment.value = ''
}

// Handle reply
const handleReply = async (parentId: number, content: string) => {
  if (!props.commentableId || props.commentableId <= 0) return

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
      // Find thread by root_id và add reply vào flat list
      const rootId = data.data.root_id
      const thread = threads.value.find(t => t.root.id === rootId)

      if (thread) {
        // Thêm reply vào flat list và sort theo created_at
        thread.replies.push(data.data)
        thread.replies.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
        thread.loaded_replies++
        thread.total_replies++

        // Update replies_count của parent comment
        if (data.data.parent_id === thread.root.id) {
          thread.root.replies_count++
        } else {
          // Tìm parent trong replies và update
          const parent = thread.replies.find(r => r.id === data.data.parent_id)
          if (parent) {
            parent.replies_count++
          }
        }
      }

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
      // Update comment in threads (flat list)
      for (const thread of threads.value) {
        if (thread.root.id === commentId) {
          Object.assign(thread.root, data.data)
          break
        }
        // Tìm trong flat list replies
        const reply = thread.replies.find(r => r.id === commentId)
        if (reply) {
          Object.assign(reply, data.data)
          break
        }
      }

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
      // Remove comment from threads
      threads.value = threads.value.filter(thread => {
        if (thread.root.id === commentId) {
          return false // Remove entire thread
        }
        // Remove từ flat list
        const beforeCount = thread.replies.length
        thread.replies = thread.replies.filter(reply => reply.id !== commentId)
        const afterCount = thread.replies.length

        if (beforeCount !== afterCount) {
          // Đã xóa một reply
          thread.loaded_replies = thread.replies.length
          thread.total_replies = thread.replies.length

          // Update replies_count của root nếu cần
          if (thread.replies.length < thread.root.replies_count) {
            thread.root.replies_count = thread.replies.length
          }
        }
        return true
      })

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
      // Update comment reaction in threads (flat list)
      for (const thread of threads.value) {
        if (thread.root.id === commentId) {
          thread.root.likes_count = data.data.likes_count
          thread.root.dislikes_count = data.data.dislikes_count
          thread.root.user_reaction = data.data.user_reaction
          break
        }
        // Tìm trong flat list replies
        const reply = thread.replies.find(r => r.id === commentId)
        if (reply) {
          reply.likes_count = data.data.likes_count
          reply.dislikes_count = data.data.dislikes_count
          reply.user_reaction = data.data.user_reaction
          break
        }
      }
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

// Load replies (deprecated - now using loadThreadReplies)
const loadReplies = async (commentId: number) => {
  // This is now handled by loadThreadReplies
  console.warn('loadReplies is deprecated, use loadThreadReplies instead')
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
  if (props.commentableId && props.commentableId > 0) {
    loadComments()
  }

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
              // Add new root comment as a new thread
              if (data.comment && !data.comment.parent_id && !threads.value.find(t => t.root.id === data.comment.id)) {
                const newThread: Thread = {
                  root: data.comment,
                  replies: [],
                  total_replies: 0,
                  loaded_replies: 0
                }
                threads.value = [newThread, ...threads.value]
              }
            })
            .listen('.comment.replied', (data: any) => {
              // Add reply to the correct thread (flat list)
              if (data.reply && data.reply.root_id) {
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
                  parent_user: data.reply.parent_user || null,
                  user_reaction: null,
                  user: data.reply.user,
                  created_at: data.reply.created_at,
                  updated_at: data.reply.created_at,
                  is_owner: data.reply.user?.id === auth.user?.id
                }

                // Find thread by root_id
                const thread = threads.value.find(t => t.root.id === data.reply.root_id)
                if (thread) {
                  // Check if reply already exists
                  const exists = thread.replies.find((r: Comment) => r.id === reply.id)

                  if (!exists) {
                    // Thêm vào flat list
                    thread.replies.push(reply)
                    thread.loaded_replies++
                    thread.total_replies++

                    // Update replies_count của parent
                    if (reply.parent_id === thread.root.id) {
                      thread.root.replies_count++
                    } else {
                      // Tìm parent trong replies và update
                      const parent = thread.replies.find((r: Comment) => r.id === reply.parent_id)
                      if (parent) {
                        parent.replies_count++
                      }
                    }
                  }
                }
              }
            })
        }
      }
    }, { immediate: true })
  }
})

// Watch for auth changes
watch(() => auth.logged, () => {
  if (auth.logged && props.commentableId && props.commentableId > 0) {
    loadComments()
  }
})

// Watch for commentableId changes (e.g., when chapter data loads)
watch(() => props.commentableId, (newId) => {
  if (newId && newId > 0 && threads.value.length === 0) {
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
