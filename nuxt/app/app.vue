<template>
  <UApp>
    <NuxtLoadingIndicator />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>

<script setup lang="ts">
// Only run on client-side
if (import.meta.client) {
  const { listenForNotifications, stopListening } = useNotifications()
  const auth = useAuthStore()

  onMounted(() => {
    if (auth.logged) {
      listenForNotifications()
    }
  })

  watch(() => auth.logged, (logged) => {
    if (logged) {
      listenForNotifications()
    } else {
      stopListening()
    }
  })

  onUnmounted(() => {
    stopListening()
  })
}
</script>
