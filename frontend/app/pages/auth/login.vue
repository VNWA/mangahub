<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

useSeoMeta({
  title: 'Đăng nhập - WebTruyện',
  description: 'Đăng nhập vào tài khoản của bạn để tiếp tục đọc truyện'
})

const toast = useToast()
const auth = useAuthStore()
const router = useRouter()
const loading = ref(false)
const guestLoading = ref(false)
const showGuestModal = ref(false)
const guestName = ref('')

const fields = [{
  name: 'email',
  type: 'text' as const,
  label: 'Email',
  placeholder: 'Nhập email của bạn',
  required: true
}, {
  name: 'password',
  label: 'Mật khẩu',
  type: 'password' as const,
  placeholder: 'Nhập mật khẩu của bạn',
  required: true
}, {
  name: 'remember',
  label: 'Ghi nhớ đăng nhập',
  type: 'checkbox' as const
}]

const schema = z.object({
  email: z.string().email('Email không hợp lệ'),
  password: z.string().min(1, 'Vui lòng nhập mật khẩu')
})

type Schema = z.output<typeof schema>

async function onSubmit(payload: FormSubmitEvent<Schema>) {
  loading.value = true
  try {
    const data = await $http<{
      ok: boolean
      message: string
      user: any
      token: string
      errors?: any
    }>('/login', {
      method: 'POST',
      body: {
        email: payload.data.email,
        password: payload.data.password
      }
    })

    if (data?.ok && data.token) {
      await auth.login(data.token)
      toast.add({
        title: 'Đăng nhập thành công',
        description: 'Chào mừng bạn trở lại!',
        color: 'success',
        icon: 'i-heroicons-check-circle'
      })
      
      // Redirect to home or intended page
      const redirect = router.currentRoute.value.query.redirect as string
      await router.push(redirect || '/')
    }
  } catch (err: any) {
    const errorData = err.data || err.response?._data
    if (errorData?.errors) {
      // Validation errors
      Object.keys(errorData.errors).forEach((key) => {
        toast.add({
          title: 'Lỗi xác thực',
          description: errorData.errors[key][0],
          color: 'error',
          icon: 'i-heroicons-exclamation-triangle'
        })
      })
    } else {
      toast.add({
        title: 'Đăng nhập thất bại',
        description: errorData?.message || err.message || 'Email hoặc mật khẩu không đúng',
        color: 'error',
        icon: 'i-heroicons-exclamation-triangle'
      })
    }
  } finally {
    loading.value = false
  }
}

async function handleGoogleLogin() {
  try {
    loading.value = true
    const data = await $http<{
      ok: boolean
      redirect_url: string
    }>('/auth/google', {
      method: 'GET'
    })

    if (data?.ok && data.redirect_url) {
      window.location.href = data.redirect_url
    }
  } catch (err: any) {
    toast.add({
      title: 'Lỗi',
      description: 'Không thể đăng nhập bằng Google',
      color: 'error',
      icon: 'i-heroicons-exclamation-triangle'
    })
    loading.value = false
  }
}

async function handleDiscordLogin() {
  try {
    loading.value = true
    const data = await $http<{
      ok: boolean
      redirect_url: string
    }>('/auth/discord', {
      method: 'GET'
    })

    if (data?.ok && data.redirect_url) {
      window.location.href = data.redirect_url
    }
  } catch (err: any) {
    toast.add({
      title: 'Lỗi',
      description: 'Không thể đăng nhập bằng Discord',
      color: 'error',
      icon: 'i-heroicons-exclamation-triangle'
    })
    loading.value = false
  }
}

async function handleGuestLogin() {
  if (!guestName.value || guestName.value.trim().length === 0) {
    toast.add({
      title: 'Lỗi',
      description: 'Vui lòng nhập tên của bạn',
      color: 'error',
      icon: 'i-heroicons-exclamation-triangle'
    })
    return
  }

  guestLoading.value = true
  try {
    const data = await $http<{
      ok: boolean
      message: string
      user: any
      token: string
    }>('/guest-login', {
      method: 'POST',
      body: {
        name: guestName.value.trim()
      }
    })

    if (data?.ok && data.token) {
      await auth.login(data.token)
      toast.add({
        title: 'Đăng nhập Guest thành công',
        description: 'Chào mừng bạn!',
        color: 'success',
        icon: 'i-heroicons-check-circle'
      })
      
      showGuestModal.value = false
      guestName.value = ''
      
      // Redirect to home
      await router.push('/')
    }
  } catch (err: any) {
    const errorData = err.data || err.response?._data
    toast.add({
      title: 'Lỗi',
      description: errorData?.message || err.message || 'Không thể đăng nhập Guest',
      color: 'error',
      icon: 'i-heroicons-exclamation-triangle'
    })
  } finally {
    guestLoading.value = false
  }
}

const providers = [{
  label: 'Google',
  icon: 'i-simple-icons-google',
  onClick: handleGoogleLogin
}, {
  label: 'Discord',
  icon: 'i-simple-icons-discord',
  onClick: handleDiscordLogin
}]
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-4 p-4 min-h-screen">
    <UPageCard class="w-full max-w-md">
      <UAuthForm 
        :loading="loading" 
        :fields="fields" 
        :schema="schema" 
        :providers="providers" 
        title="Chào mừng trở lại"
        icon="i-lucide-lock" 
        @submit="onSubmit">
        <template #description>
          Chưa có tài khoản? <ULink to="/auth/register" class="text-primary font-medium">Đăng ký ngay</ULink>.
        </template>

        <template #password-hint>
          <ULink to="/auth/forgot-password" class="text-primary font-medium" tabindex="-1">Quên mật khẩu?</ULink>
        </template>

        <template #footer>
          Bằng việc đăng nhập, bạn đồng ý với <ULink to="/" class="text-primary font-medium">Điều khoản dịch vụ</ULink> của chúng tôi.
        </template>
      </UAuthForm>

      <!-- Guest Login -->
      <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
        <div class="text-center mb-3">
          <p class="text-sm text-slate-600 dark:text-slate-400">Hoặc</p>
        </div>
        <UButton
          @click="showGuestModal = true"
          variant="outline"
          color="neutral"
          block
          :loading="guestLoading"
        >
          <UIcon name="i-heroicons-user" class="w-4 h-4 mr-2" />
          Đăng nhập Guest (Chỉ cần tên)
        </UButton>
      </div>
    </UPageCard>

    <!-- Guest Login Modal -->
    <UModal v-model="showGuestModal">
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold">Đăng nhập Guest</h3>
        </template>

        <div class="space-y-4">
          <p class="text-sm text-slate-600 dark:text-slate-400">
            Chỉ cần nhập tên của bạn để bắt đầu đọc truyện. Tài khoản guest sẽ tự động bị xóa sau 30 ngày.
          </p>
          
          <UInput
            v-model="guestName"
            label="Tên của bạn"
            placeholder="Nhập tên"
            :disabled="guestLoading"
          />

          <div class="flex gap-2">
            <UButton
              @click="handleGuestLogin"
              color="primary"
              block
              :loading="guestLoading"
              :disabled="!guestName || guestName.trim().length === 0"
            >
              Đăng nhập
            </UButton>
            <UButton
              @click="showGuestModal = false"
              variant="ghost"
              color="neutral"
              :disabled="guestLoading"
            >
              Hủy
            </UButton>
          </div>
        </div>
      </UCard>
    </UModal>
  </div>
</template>
