<template>
    <div>
        <div class="space-y-6">
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Cài đặt tài khoản</h2>

            <!-- Profile Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 space-y-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Thông tin cá nhân</h3>

                <!-- Name Setting -->
                <div>
                    <label class="block text-sm font-semibold text-zinc-900 dark:text-white mb-2">
                        Tên hiển thị
                    </label>
                    <UInput v-model="profileForm.name" type="text" placeholder="Nhập tên của bạn" />
                </div>

                <!-- Email Setting -->
                <div>
                    <label class="block text-sm font-semibold text-zinc-900 dark:text-white mb-2">
                        Email
                    </label>
                    <UInput :model-value="auth.user?.email" type="email" disabled />
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Email không thể thay đổi
                    </p>
                </div>

                <!-- Save Profile Button -->
                <div class="flex gap-3 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <UButton @click="saveProfile" :loading="savingProfile" color="primary" label="Lưu thông tin" />
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 space-y-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Cài đặt thông báo</h3>

                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Thông báo qua Email</h4>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <UCheckbox v-model="settingsForm.notify_email_new_chapter" />
                                <div class="flex-1">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        Nhận email khi có chương mới
                                    </span>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Nhận thông báo qua email khi truyện bạn theo dõi có chương mới
                                    </p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <UCheckbox v-model="settingsForm.notify_email_comment_reply" />
                                <div class="flex-1">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        Nhận email khi có phản hồi bình luận
                                    </span>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Nhận thông báo qua email khi ai đó phản hồi bình luận của bạn
                                    </p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <UCheckbox v-model="settingsForm.notify_email_recommendations" />
                                <div class="flex-1">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        Nhận email về truyện được gợi ý
                                    </span>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Nhận email về các truyện được gợi ý dựa trên sở thích của bạn
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Thông báo Push</h4>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <UCheckbox v-model="settingsForm.notify_push_new_chapter" />
                                <div class="flex-1">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        Nhận thông báo push khi có chương mới
                                    </span>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Nhận thông báo trên trình duyệt khi truyện bạn theo dõi có chương mới
                                    </p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <UCheckbox v-model="settingsForm.notify_push_comment_reply" />
                                <div class="flex-1">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        Nhận thông báo push khi có phản hồi bình luận
                                    </span>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Nhận thông báo trên trình duyệt khi ai đó phản hồi bình luận của bạn
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Save Settings Button -->
                <div class="flex gap-3 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <UButton @click="saveSettings" :loading="savingSettings" color="primary"
                        label="Lưu cài đặt thông báo" />
                </div>
            </div>

            <!-- Privacy Settings -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 space-y-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Quyền riêng tư</h3>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <UCheckbox v-model="settingsForm.privacy_public_profile" />
                        <div class="flex-1">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                Cho phép mọi người xem hồ sơ của tôi
                            </span>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Khi bật, người khác có thể xem thông tin công khai trong hồ sơ của bạn
                            </p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <UCheckbox v-model="settingsForm.privacy_show_reading_history" />
                        <div class="flex-1">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                Hiển thị lịch sử đọc công khai
                            </span>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Cho phép người khác xem danh sách truyện bạn đã đọc
                            </p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <UCheckbox v-model="settingsForm.privacy_show_favorites" />
                        <div class="flex-1">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                Hiển thị truyện yêu thích công khai
                            </span>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Cho phép người khác xem danh sách truyện bạn yêu thích
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Save Privacy Button -->
                <div class="flex gap-3 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <UButton @click="saveSettings" :loading="savingSettings" color="primary"
                        label="Lưu cài đặt quyền riêng tư" />
                </div>
            </div>

            <!-- Danger Zone -->
            <div
                class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow-md p-6">
                <h3 class="font-semibold text-red-900 dark:text-red-200 mb-4">Vùng nguy hiểm</h3>
                <p class="text-sm text-red-800 dark:text-red-300 mb-4">
                    Xóa tài khoản sẽ xóa vĩnh viễn tất cả dữ liệu của bạn. Hành động này không thể hoàn tác.
                </p>
                <UButton color="red" variant="outline" label="Xóa tài khoản" @click="showDeleteConfirm()" />
            </div>
        </div>

    </div>
</template>

<script setup lang="ts">
import { ModalDeleteAccount } from '#components'

const auth = useAuthStore()
const toast = useToast()
const overlay = useOverlay()

const profileForm = ref({
    name: ''
})

const settingsForm = ref({
    notify_email_new_chapter: true,
    notify_email_comment_reply: true,
    notify_email_recommendations: true,
    notify_push_new_chapter: true,
    notify_push_comment_reply: true,
    privacy_public_profile: false,
    privacy_show_reading_history: false,
    privacy_show_favorites: false
})

const savingProfile = ref(false)
const savingSettings = ref(false)
const deletingAccount = ref(false)

// Load user data
const loadUserData = () => {
    if (auth.user) {
        profileForm.value.name = auth.user.name || ''

        // Load settings from user
        settingsForm.value.notify_email_new_chapter = auth.user.notify_email_new_chapter ?? true
        settingsForm.value.notify_email_comment_reply = auth.user.notify_email_comment_reply ?? true
        settingsForm.value.notify_email_recommendations = auth.user.notify_email_recommendations ?? true
        settingsForm.value.notify_push_new_chapter = auth.user.notify_push_new_chapter ?? true
        settingsForm.value.notify_push_comment_reply = auth.user.notify_push_comment_reply ?? true
        settingsForm.value.privacy_public_profile = auth.user.privacy_public_profile ?? false
        settingsForm.value.privacy_show_reading_history = auth.user.privacy_show_reading_history ?? false
        settingsForm.value.privacy_show_favorites = auth.user.privacy_show_favorites ?? false
    }
}

// Save profile
const saveProfile = async () => {
    savingProfile.value = true
    try {
        const data = await $http<{
            ok: boolean
            message: string
            user: any
        }>('/profile', {
            method: 'PUT',
            body: {
                name: profileForm.value.name
            }
        })

        if (data?.ok) {
            toast.add({
                title: 'Thành công',
                description: data.message || 'Cập nhật hồ sơ thành công',
                color: 'success'
            })

            // Update auth store
            if (data.user) {
                auth.user = { ...auth.user, ...data.user }
            }
        }
    } catch (error: any) {
        const errorData = error.data || error.response?._data
        toast.add({
            title: 'Lỗi',
            description: errorData?.message || 'Không thể cập nhật hồ sơ',
            color: 'error'
        })
    } finally {
        savingProfile.value = false
    }
}

// Save settings
const saveSettings = async () => {
    savingSettings.value = true
    try {
        const data = await $http<{
            ok: boolean
            message: string
            user: any
        }>('/settings', {
            method: 'PUT',
            body: settingsForm.value
        })

        if (data?.ok) {
            toast.add({
                title: 'Thành công',
                description: data.message || 'Cập nhật cài đặt thành công',
                color: 'success'
            })

            // Update auth store
            if (data.user) {
                auth.user = { ...auth.user, ...data.user }
            }
        }
    } catch (error: any) {
        const errorData = error.data || error.response?._data
        toast.add({
            title: 'Lỗi',
            description: errorData?.message || 'Không thể cập nhật cài đặt',
            color: 'error'
        })
    } finally {
        savingSettings.value = false
    }
}

const deleteAccount = async () => {
    deletingAccount.value = true
    try {
        // TODO: Implement delete account endpoint
        toast.add({
            title: 'Thông báo',
            description: 'Tính năng xóa tài khoản đang được phát triển',
            color: 'info'
        })
    } catch (error: any) {
        const errorData = error.data || error.response?._data
        toast.add({
            title: 'Lỗi',
            description: errorData?.message || 'Không thể xóa tài khoản',
            color: 'error'
        })
    } finally {
        deletingAccount.value = false
    }
}

const showDeleteConfirm = () => {
    const modal = overlay.create(ModalDeleteAccount)
    modal.open({
        onDelete: deleteAccount,
        onClose: () => modal.close()
    })
}

onMounted(() => {
    loadUserData()
})

// Watch for user changes
watch(() => auth.user, () => {
    loadUserData()
}, { deep: true })
</script>
