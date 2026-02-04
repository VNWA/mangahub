export default defineNuxtPlugin(() => {
  const { listenForNotifications, stopListening } = useNotifications()
  const auth = useAuthStore()

  if (auth.logged) {
    listenForNotifications()
  } else {
    stopListening()
  }

})
