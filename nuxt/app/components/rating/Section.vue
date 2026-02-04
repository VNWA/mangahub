<template>
  <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-4">Đánh giá</h3>

    <!-- Average Rating -->
    <div class="flex items-center gap-4 mb-6 pb-6 border-b border-zinc-200 dark:border-zinc-700">
      <div class="text-center">
        <div class="text-4xl font-bold text-zinc-900 dark:text-white">
          {{ averageRating.toFixed(1) }}
        </div>
        <div class="flex items-center justify-center gap-1 mt-1">
          <UIcon v-for="i in 5" :key="i"
            :name="i <= Math.round(averageRating) ? 'i-heroicons-star-solid' : 'i-heroicons-star'" :class="[
              'w-5 h-5',
              i <= Math.round(averageRating) ? 'text-yellow-400' : 'text-zinc-300 dark:text-zinc-600'
            ]" />
        </div>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
          {{ totalRatings }} đánh giá
        </p>
      </div>

      <!-- Rating Distribution -->
      <div class="flex-1 space-y-2">
        <div v-for="star in 5" :key="star" class="flex items-center gap-2">
          <span class="text-sm text-zinc-600 dark:text-zinc-400 w-8">{{ 6 - star }}⭐</span>
          <div class="flex-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
            <div class="bg-yellow-400 h-2 rounded-full transition-all"
              :style="{ width: `${getPercentage(6 - star)}%` }"></div>
          </div>
          <span class="text-sm text-zinc-600 dark:text-zinc-400 w-8 text-right">
            {{ ratingDistribution[6 - star] || 0 }}
          </span>
        </div>
      </div>
    </div>

    <!-- User Rating Form -->
    <div v-if="auth.logged" class="space-y-4">
      <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-zinc-900 dark:text-white">Đánh giá của bạn:</span>
        <div class="flex gap-1">
          <button v-for="i in 5" :key="i" @click="submitRating(i)" :class="[
            'transition-colors cursor-pointer',
            i <= currentDisplayRating
              ? 'text-yellow-400'
              : 'text-zinc-300 dark:text-zinc-600 hover:text-yellow-400'
          ]">
            <UIcon :name="i <= currentDisplayRating ? 'i-heroicons-star-solid' : 'i-heroicons-star'" class="w-6 h-6" />
          </button>
        </div>
        <span v-if="currentDisplayRating > 0" class="text-sm text-zinc-600 dark:text-zinc-400">
          ({{ currentDisplayRating }}/5)
        </span>
      </div>

      <div v-if="userRating || showReviewForm" class="space-y-2">
        <UTextarea v-model="reviewText" placeholder="Viết đánh giá chi tiết (tùy chọn)..." :rows="3" />
        <div class="flex gap-2">
          <UButton @click="submitReview" color="primary" size="sm" :loading="submitting" :disabled="!selectedRating">
            {{ userRating ? 'Cập nhật' : 'Gửi đánh giá' }}
          </UButton>
          <UButton v-if="userRating" @click="deleteRating" variant="ghost" color="error" size="sm" :loading="deleting">
            Xóa đánh giá
          </UButton>
        </div>
      </div>
    </div>

    <!-- Login Prompt -->
    <div v-else class="text-center py-4">
      <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
        Đăng nhập để đánh giá
      </p>
      <UButton to="/auth/login" color="primary" size="sm">
        Đăng nhập
      </UButton>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  mangaId: number
}

const props = defineProps<Props>()
const auth = useAuthStore()
const toast = useToast()

const averageRating = ref(0)
const totalRatings = ref(0)
const ratingDistribution = ref<Record<number, number>>({
  5: 0,
  4: 0,
  3: 0,
  2: 0,
  1: 0,
})
const userRating = ref<{
  rating: number
  review: string | null
  created_at: string
} | null>(null)
const selectedRating = ref(0)
const reviewText = ref('')
const showReviewForm = ref(false)
const submitting = ref(false)
const deleting = ref(false)

// Computed: Hiển thị rating đang chọn hoặc rating đã có
const currentDisplayRating = computed(() => {
  // Nếu đang chọn sao mới (selectedRating > 0), hiển thị selectedRating
  // Nếu không, hiển thị userRating nếu có
  return selectedRating.value > 0 ? selectedRating.value : (userRating.value?.rating || 0)
})

// Load rating data
const loadRating = async () => {
  try {
    const data = await $http<{
      ok: boolean
      data: {
        average_rating: number
        total_ratings: number
        rating_distribution: Record<number, number>
        user_rating: {
          rating: number
          review: string | null
          created_at: string
        } | null
      }
    }>(`/mangas/${props.mangaId}/rating`)

    if (data?.ok && data.data) {
      averageRating.value = data.data.average_rating
      totalRatings.value = data.data.total_ratings
      ratingDistribution.value = data.data.rating_distribution
      userRating.value = data.data.user_rating
      if (userRating.value) {
        selectedRating.value = userRating.value.rating
        reviewText.value = userRating.value.review || ''
      } else {
        // Reset nếu không có user rating
        selectedRating.value = 0
        reviewText.value = ''
        showReviewForm.value = false
      }
    }
  } catch (error) {
    console.error('Failed to load rating:', error)
  }
}

const getPercentage = (star: number) => {
  if (totalRatings.value === 0) return 0
  return ((ratingDistribution.value[star] || 0) / totalRatings.value) * 100
}

const submitRating = async (rating: number) => {
  if (!auth.logged) {
    toast.add({
      title: 'Lỗi',
      description: 'Vui lòng đăng nhập để đánh giá',
      color: 'error'
    })
    return
  }

  selectedRating.value = rating
  showReviewForm.value = true
}

const submitReview = async () => {
  if (!selectedRating.value || !auth.logged) return

  submitting.value = true
  try {
    const data = await $http<{
      ok: boolean
    }>(`/mangas/${props.mangaId}/rating`, {
      method: 'POST',
      body: {
        rating: selectedRating.value,
        review: reviewText.value.trim() || null
      }
    })

    if (data?.ok) {
      await loadRating()
      toast.add({
        title: 'Thành công',
        description: 'Đã gửi đánh giá',
        color: 'success'
      })
      showReviewForm.value = false
      // selectedRating sẽ được set lại từ userRating sau khi loadRating
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || 'Không thể gửi đánh giá',
      color: 'error'
    })
  } finally {
    submitting.value = false
  }
}

const deleteRating = async () => {
  if (!auth.logged) return

  deleting.value = true
  try {
    const data = await $http<{
      ok: boolean
    }>(`/mangas/${props.mangaId}/rating`, {
      method: 'DELETE'
    })

    if (data?.ok) {
      userRating.value = null
      selectedRating.value = 0
      reviewText.value = ''
      await loadRating()
      toast.add({
        title: 'Thành công',
        description: 'Đã xóa đánh giá',
        color: 'success'
      })
    }
  } catch (error: any) {
    const errorData = error.data || error.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || 'Không thể xóa đánh giá',
      color: 'error'
    })
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  loadRating()
})

watch(() => auth.logged, () => {
  if (auth.logged) {
    loadRating()
  }
})
</script>
