<script setup lang="ts">
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

onMounted(async () => {
  const token = route.query.token as string
  const error = route.query.error as string

  if (error) {
    toast.add({
      title: 'Đăng nhập thất bại',
      description: error,
      color: 'error',
      icon: 'i-heroicons-exclamation-triangle'
    })
    await router.push('/auth/login')
    return
  }

  if (token) {
    try {
      await auth.login(token)
      toast.add({
        title: 'Đăng nhập thành công',
        description: 'Chào mừng bạn trở lại!',
        color: 'success',
        icon: 'i-heroicons-check-circle'
      })
      await router.push('/')
    } catch (err: any) {
      toast.add({
        title: 'Lỗi',
        description: 'Không thể xác thực token',
        color: 'error',
        icon: 'i-heroicons-exclamation-triangle'
      })
      await router.push('/auth/login')
    }
  } else {
    await router.push('/auth/login')
  }
})
</script>

<template>
  <div class="flex items-center justify-center min-h-screen">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
      <p class="text-zinc-600 dark:text-zinc-400">Đang xử lý đăng nhập...</p>
    </div>
  </div>
</template>
