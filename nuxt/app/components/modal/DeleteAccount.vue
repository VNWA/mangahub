<template>
  <UModal>
    <UCard>
      <template #header>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Xác nhận xóa tài khoản</h3>
      </template>

      <div class="space-y-4">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
          Bạn có chắc chắn muốn xóa tài khoản? Hành động này không thể hoàn tác.
        </p>
      </div>

      <template #footer>
        <div class="flex gap-3 justify-end">
          <UButton color="neutral" variant="ghost" label="Hủy" @click="close" :disabled="loading" />
          <UButton color="error" label="Xóa tài khoản" @click="handleDelete" :loading="loading" />
        </div>
      </template>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
interface Props {
  onDelete: () => Promise<void>
  onClose?: () => void
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
}>()

const loading = ref(false)

const close = () => {
  emit('close')
  props.onClose?.()
}

const handleDelete = async () => {
  loading.value = true
  try {
    await props.onDelete()
    close()
  } finally {
    loading.value = false
  }
}
</script>
