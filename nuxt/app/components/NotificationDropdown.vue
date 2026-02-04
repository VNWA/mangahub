<template>
  <UPopover v-model:open="isOpen" :popper="{ placement: 'bottom-end' }">
    <UButton variant="ghost" color="neutral" square size="sm" class="relative"
      :class="{ 'ring-2 ring-purple-500 dark:ring-purple-400': hasUnread }">
      <UIcon name="i-heroicons-bell" class="w-5 h-5" />
      <span v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </UButton>

    <template #content>
      <div class="w-80 max-h-96 overflow-y-auto">
        <!-- Header -->
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
          <h3 class="font-semibold text-zinc-900 dark:text-white">Thông báo</h3>
          <UButton v-if="notifications.length > 0" variant="ghost" size="xs" @click="markAllAsRead"
            :disabled="unreadCount === 0">
            Đánh dấu đã đọc
          </UButton>
        </div>

        <!-- Notifications List -->
        <div v-if="loading" class="p-4 space-y-3">
          <div v-for="i in 3" :key="i" class="animate-pulse">
            <div class="h-16 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
          </div>
        </div>

        <div v-else-if="notifications.length === 0" class="p-8 text-center">
          <UIcon name="i-heroicons-bell-slash" class="w-12 h-12 text-zinc-400 mx-auto mb-2" />
          <p class="text-sm text-zinc-600 dark:text-zinc-400">Chưa có thông báo nào</p>
        </div>

        <div v-else class="divide-y divide-zinc-200 dark:divide-zinc-700">
          <button v-for="notification in notifications" :key="notification.id"
            @click="handleNotificationClick(notification)" :class="[
              'w-full p-4 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors',
              !notification.read_at && 'bg-blue-50/50 dark:bg-blue-900/20'
            ]">
            <div class="flex gap-3">
              <div class="shrink-0">
                <div :class="[
                  'w-2 h-2 rounded-full mt-2',
                  !notification.read_at ? 'bg-blue-500' : 'bg-transparent'
                ]" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-zinc-900 dark:text-white mb-1">
                  {{ notification.title || 'Thông báo mới' }}
                </p>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2">
                  {{ notification.message || notification.data?.message || '' }}
                </p>
                <p class="text-xs text-zinc-500 mt-1">
                  {{ formatTime(notification.created_at) }}
                </p>
              </div>
            </div>
          </button>
        </div>

        <!-- Footer -->
        <div v-if="notifications.length > 0" class="p-3 border-t border-zinc-200 dark:border-zinc-700">
          <UButton variant="ghost" color="primary" block @click="viewAllNotifications">
            Xem tất cả
          </UButton>
        </div>
      </div>
    </template>
  </UPopover>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const router = useRouter()

interface Notification {
  id: string | number
  type: string
  title?: string
  message?: string
  data?: any
  read_at: string | null
  created_at: string
}

const notifications = ref<Notification[]>([])
const loading = ref(false)
const isOpen = ref(false)
const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length)
const hasUnread = computed(() => unreadCount.value > 0)

// Load notifications
const loadNotifications = async () => {
  if (!auth.logged) return

  try {
    loading.value = true
    const data = await $http<{
      ok: boolean
      data: Notification[]
      unread_count: number
    }>('/notifications', {
      query: {
        per_page: 20,
      },
    })

    if (data?.ok && data.data) {
      notifications.value = data.data.map((n: any) => ({
        id: n.id,
        type: n.type,
        title: n.title || 'Thông báo',
        message: n.message || '',
        data: n.data || {},
        read_at: n.read_at,
        created_at: n.created_at,
      }))
    }
  } catch (error) {
    console.error('Failed to load notifications:', error)
  } finally {
    loading.value = false
  }
}

// Watch for popover open
watch(isOpen, (open) => {
  if (open && auth.logged) {
    loadNotifications()
  }
})

// Mark all as read
const markAllAsRead = async () => {
  if (unreadCount.value === 0) return

  try {
    await $http('/notifications/mark-all-read', {
      method: 'POST',
    })
    notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }))
  } catch (error) {
    console.error('Failed to mark all as read:', error)
  }
}

// Handle notification click
const handleNotificationClick = async (notification: Notification) => {
  // Mark as read
  if (!notification.read_at) {
    try {
      await $http(`/notifications/${notification.id}/read`, {
        method: 'POST',
      })
      notification.read_at = new Date().toISOString()
    } catch (error) {
      console.error('Failed to mark notification as read:', error)
    }
  }

  // Navigate based on type
  if (notification.type === 'new_chapter') {
    const mangaSlug = notification.data?.manga_slug || notification.data?.manga?.slug
    const chapterSlug = notification.data?.chapter_slug || notification.data?.chapter?.slug
    if (mangaSlug && chapterSlug) {
      router.push(`/${mangaSlug}/${chapterSlug}`)
    } else if (mangaSlug) {
      router.push(`/${mangaSlug}`)
    }
  } else if (notification.type === 'comment_reply' && notification.data?.manga_slug) {
    // Navigate to manga page with comment ID in hash for scrolling
    const commentId = notification.data.comment_id || notification.data.reply?.id
    const route = `/${notification.data.manga_slug}${commentId ? `#comment-${commentId}` : '#comments'}`

    // Navigate first
    router.push(route).then(() => {
      // Wait for page to load, then handle scrolling
      nextTick(() => {
        // First, ensure comments tab is active
        const commentsTab = document.querySelector('[data-tab="comments"]')
        if (commentsTab) {
          (commentsTab as HTMLElement).click()
        }

        // Then scroll to comment or comments section
        setTimeout(() => {
          if (commentId) {
            // Try to scroll to the specific comment
            const element = document.getElementById(`comment-${commentId}`)
            if (element) {
              element.scrollIntoView({ behavior: 'smooth', block: 'center' })
              // Highlight the comment briefly
              element.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2', 'rounded-lg')
              setTimeout(() => {
                element.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2')
              }, 2000)
            } else {
              // Fallback: scroll to comments section
              const commentsSection = document.getElementById('comments-section')
              if (commentsSection) {
                commentsSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
              }
            }
          } else {
            // Scroll to comments section
            const commentsSection = document.getElementById('comments-section')
            if (commentsSection) {
              commentsSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
            }
          }
        }, 800) // Wait a bit longer for tab to switch
      })
    })
  }
}

// View all notifications
const viewAllNotifications = () => {
  router.push('/notifications')
}

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

// Listen for real-time notifications
let notificationHandler: ((notification: any) => void) | null = null

onMounted(() => {
  if (auth.logged) {
    loadNotifications()

    // Listen for new notifications via Echo
    const echo = useEcho()
    if (echo && auth.user?.id) {
      notificationHandler = (notification: any) => {
        // Add to top of list if not already exists
        const existingIndex = notifications.value.findIndex(n => n.id === notification.id)
        if (existingIndex === -1) {
          notifications.value.unshift({
            id: notification.id || `echo-${Date.now()}`,
            type: notification.type,
            title: notification.title || 'Thông báo',
            message: notification.message || '',
            data: notification.data || {},
            read_at: null,
            created_at: new Date().toISOString(),
          })
        }
      }

      echo.private(`user.${auth.user.id}`).notification(notificationHandler)
    }
  }
})

onUnmounted(() => {
  const echo = useEcho()
  if (echo && auth.user?.id && notificationHandler) {
    try {
      echo.leave(`user.${auth.user.id}`)
    } catch (error) {
      console.error('Error leaving channel:', error)
    }
  }
})

watch(() => auth.logged, (logged) => {
  if (logged) {
    loadNotifications()
  } else {
    notifications.value = []
  }
})
</script>
