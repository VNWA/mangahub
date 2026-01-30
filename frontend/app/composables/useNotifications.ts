import { useAuthStore } from '~/stores/auth'

export const useNotifications = () => {
  const auth = useAuthStore()
  const toast = useToast()

  const listenForNotifications = () => {
    // Only run on client-side
    if (typeof window === 'undefined') return

    const echo = useEcho()
    if (!echo || !auth.logged || !auth.user?.id) return


    // Listen for user-specific notifications (private channel)
    echo.private(`user.${auth.user.id}`)
      .notification((notification: any) => {
        handleNotification(notification)
      })
  }

  const handleNotification = (notification: any) => {
    const { type, message, title, data } = notification

    const toastId = toast.add({
      title: title || 'Thông báo',
      description: message,
      color: 'info',
      icon: 'i-heroicons-bell'
    })

    // Handle navigation on toast click (if supported)
    // Navigation will be handled via NotificationDropdown when user clicks notification

    // Handle specific notification types
    switch (type) {
      case 'comment_reply':
        // Notification will be added to dropdown via Echo listener
        console.log('Comment reply notification:', data)
        break
      case 'new_chapter':
        // Notification will be added to dropdown via Echo listener
        console.log('New chapter notification:', data)
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
