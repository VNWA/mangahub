<template>
  <UModal>
    <UCard>
      <template #header>
        <h3 class="text-lg font-semibold">Báo cáo chapter lỗi</h3>
      </template>

      <div class="space-y-4">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
          Bạn có thể báo cáo chapter này nếu gặp vấn đề như: ảnh lỗi, thiếu trang, nội dung sai...
        </p>

        <UInput v-model="reason" label="Lý do" placeholder="Ví dụ: Ảnh bị lỗi, thiếu trang..." :disabled="loading" />

        <UTextarea v-model="description" label="Mô tả chi tiết (tùy chọn)" placeholder="Mô tả chi tiết vấn đề..."
          :disabled="loading" :rows="3" />
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" label="Hủy" @click="close" :disabled="loading" />
          <UButton color="error" label="Gửi báo cáo" @click="handleReport" :loading="loading" />
        </div>
      </template>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
interface Props {
  manga_slug: string
  chapter_slug: string
  onClose?: () => void
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
}>()

const reason = ref('Chapter lỗi')
const description = ref('')
const loading = ref(false)
const toast = useToast()

const close = () => {
  emit('close')
  props.onClose?.()
}

const handleReport = async () => {
  if (!reason.value.trim()) {
    toast.add({
      title: 'Lỗi',
      description: 'Vui lòng nhập lý do báo cáo',
      color: 'error'
    })
    return
  }

  loading.value = true
  try {
    const data = await $http<{
      ok: boolean
      message: string
    }>(`/mangas/${props.manga_slug}/chapters/${props.chapter_slug}/report`, {
      method: 'POST',
      body: {
        reason: reason.value,
        description: description.value || null,
      }
    })

    if (data?.ok) {
      toast.add({
        title: 'Thành công',
        description: data.message || 'Báo cáo đã được gửi thành công',
        color: 'success'
      })
      close()
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || 'Không thể gửi báo cáo',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}
</script>
