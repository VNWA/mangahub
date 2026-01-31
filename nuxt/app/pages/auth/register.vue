<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

useSeoMeta({
  title: 'Đăng ký - WebTruyện',
  description: 'Tạo tài khoản để bắt đầu đọc truyện'
})

const toast = useToast()
const auth = useAuthStore()
const router = useRouter()
const loading = ref(false)

const fields = computed(() => [
  {
    name: 'name',
    type: 'text' as const,
    label: 'Họ và tên',
    placeholder: 'Nhập họ và tên của bạn',
    required: true,
    disabled: loading.value
  },
  {
    name: 'email',
    type: 'text' as const,
    label: 'Email',
    placeholder: 'Nhập email của bạn',
    required: true,
    disabled: loading.value
  },
  {
    name: 'password',
    type: 'password' as const,
    label: 'Mật khẩu',
    placeholder: 'Nhập mật khẩu (tối thiểu 8 ký tự)',
    required: true,
    disabled: loading.value
  },
  {
    name: 'password_confirmation',
    type: 'password' as const,
    label: 'Xác nhận mật khẩu',
    placeholder: 'Nhập lại mật khẩu',
    required: true,
    disabled: loading.value
  },
])

const schema = z.object({
  name: z.string().min(1, 'Vui lòng nhập họ và tên'),
  email: z.string().email('Email không hợp lệ'),
  password: z.string().min(8, 'Mật khẩu phải có ít nhất 8 ký tự'),
  password_confirmation: z.string().min(8, 'Mật khẩu phải có ít nhất 8 ký tự')
}).superRefine((data, ctx) => {
  if (data.password !== data.password_confirmation) {
    ctx.addIssue({
      code: z.ZodIssueCode.custom,
      message: 'Mật khẩu xác nhận không khớp',
      path: ['password_confirmation']
    })
  }
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
    }>('/register', {
      method: 'POST',
      body: {
        name: payload.data.name,
        email: payload.data.email,
        password: payload.data.password,
        password_confirmation: payload.data.password_confirmation
      }
    })

    if (data?.ok && data.token) {
      await auth.login(data.token)
      toast.add({
        title: 'Đăng ký thành công',
        description: 'Chào mừng bạn đến với WebTruyện!',
        color: 'success',
        icon: 'i-heroicons-check-circle'
      })
      
      // Redirect to home
      await router.push('/')
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
        title: 'Đăng ký thất bại',
        description: errorData?.message || err.message || 'Không thể tạo tài khoản',
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
      description: 'Không thể đăng ký bằng Google',
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
      description: 'Không thể đăng ký bằng Discord',
      color: 'error',
      icon: 'i-heroicons-exclamation-triangle'
    })
    loading.value = false
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
        title="Tạo tài khoản"
        :submit="{ label: 'Đăng ký' }" 
        @submit="onSubmit">
        <template #description>
          Đã có tài khoản? <ULink to="/auth/login" class="text-primary font-medium">Đăng nhập</ULink>.
        </template>

        <template #footer>
          Bằng việc đăng ký, bạn đồng ý với <ULink to="/" class="text-primary font-medium">Điều khoản dịch vụ</ULink> của chúng tôi.
        </template>
      </UAuthForm>
    </UPageCard>
  </div>
</template>