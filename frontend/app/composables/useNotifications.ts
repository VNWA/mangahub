import { useAuthStore } from '~/stores/auth'

export const useNotifications = () => {
  const auth = useAuthStore()
  const toast = useToast()

  const listenForNotifications = () => {
    // Only run on client-side
    if (typeof window === 'undefined') return

    const echo = useEcho()
    if (!echo || !auth.logged || !auth.user?.id) return

    console.log('🔔 Listening for notifications on channel:', `user.${auth.user.id}`)

    // Listen for user-specific notifications (private channel)
    echo.private(`user.${auth.user.id}`)
      .notification((notification: any) => {
        console.log('📬 Received notification:', notification)
        handleNotification(notification)
      })
  }

  const handleNotification = (notification: any) => {
    const { type, message, data } = notification

    toast.add({
      title: 'Thông báo',
      description: message,
      color: 'info',
      icon: 'i-heroicons-bell'
    })

    // Handle specific notification types
    switch (type) {
      case 'comment_reply':
        // Could navigate to comment or show in notification center
        break
      case 'new_chapter':
        // Could navigate to chapter or show in notification center
        if (data?.chapter) {
          // Store notification for later viewing
          console.log('New chapter:', data.chapter)
        }
        break
    }
  }

  const stopListening = () => {
    // Only run on client-side
    if (typeof window === 'undefined') return

    const echo = useEcho()
    if (echo && auth.user?.id) {
      echo.leave(`user.${auth.user.id}`)
    }
  }

  return {
    listenForNotifications,
    stopListening,
  }
}
