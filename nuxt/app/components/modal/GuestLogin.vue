<template>
  <UModal>
    <UCard>
      <template #header>
        <h3 class="text-lg font-semibold">Đăng nhập Guest</h3>
      </template>

      <div class="space-y-4">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
          Chỉ cần nhập tên của bạn để bắt đầu đọc truyện. Tài khoản guest sẽ tự động bị xóa sau 30 ngày.
        </p>

        <UInput v-model="guestName" label="Tên của bạn" placeholder="Nhập tên" :disabled="loading" />

        <div class="flex gap-2">
          <UButton @click="handleLogin" color="primary" block :loading="loading"
            :disabled="!guestName || guestName.trim().length === 0">
            Đăng nhập
          </UButton>
          <UButton @click="close" variant="ghost" color="neutral" :disabled="loading">
            Hủy
          </UButton>
        </div>
      </div>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
interface Props {
  onLogin: (name: string) => Promise<void>
  onClose?: () => void
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
}>()

const guestName = ref('')
const loading = ref(false)

const close = () => {
  emit('close')
  props.onClose?.()
}

const handleLogin = async () => {
  if (!guestName.value.trim()) return

  loading.value = true
  try {
    await props.onLogin(guestName.value.trim())
    close()
  } finally {
    loading.value = false
  }
}
</script>
