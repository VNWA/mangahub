<template>
    <div class="w-full max-w-[200px]">
        <div class="relative aspect-square">
            <!-- Loading -->
            <div v-if="loading" class="absolute inset-0 z-30 bg-black/60 rounded-md flex items-center justify-center">
                <span class="text-white text-sm">Uploading...</span>
            </div>

            <!-- Remove -->
            <button v-if="modelValue && !loading" type="button" class="absolute -top-2 -right-2 z-20 rounded-full p-1
                       bg-red-600 hover:bg-red-700 text-white" @click="removeImage">
                <X class="w-4 h-4" />
            </button>

            <!-- Image box -->
            <div class="relative w-full h-full overflow-hidden rounded-md
                       border border-gray-200 dark:border-gray-800 group cursor-pointer" @click="handleClick">
                <img :src="getStorageUrl(modelValue)" class="w-full h-full object-cover" alt="Image" />


                <!-- Hover overlay -->
                <div v-if="!loading" class="absolute inset-0 bg-black/60 flex items-center justify-center
                           opacity-0 group-hover:opacity-100 transition">
                    <p class="text-white text-sm">Click to upload</p>
                </div>
            </div>
        </div>

        <input ref="fileInput" type="file" class="hidden" accept="image/*,.ico" @change="handleFileChange" />
    </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import axios from 'axios'
import { X } from 'lucide-vue-next'
import { getStorageUrl } from '@/utils/storage'
import upload from '@/routes/upload'
import { useToast } from 'vue-toastification';
const toast = useToast();

const props = defineProps<{
    modelValue: string,
    width?: number
    height?: number
    format?: 'webp' | 'jpeg' | 'png'
    type?: 'image' | 'ico'
}>()

const emits = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const loading = ref(false)

// click overlay → open file picker
const handleClick = () => {
    if (loading.value) return
    fileInput.value?.click()
}

// chọn file xong
const handleFileChange = async (e: Event) => {
    const input = e.target as HTMLInputElement
    const file = input.files?.[0]
    if (!file) return

    const isImage = file.type.startsWith('image/')
    const isIco = file.name.toLowerCase().endsWith('.ico')

    if (!isImage && !isIco) {
        toast.error('Chỉ cho phép image hoặc file .ico')
        input.value = ''
        return
    } else {
        if (props.type === 'ico' && !isIco) {
            toast.error('Chỉ cho phép file .ico')
            input.value = ''
            return
        }
        if (props.type === 'image' && !isImage) {
            toast.error('Chỉ cho phép file image')
            input.value = ''
            return
        }
    }
    if (file.size > 2 * 1024 * 1024) {
        toast.error('File quá lớn (tối đa 2MB)')
        input.value = ''
        return
    }
    await uploadImage(file)

    // reset input để chọn lại cùng file vẫn trigger change
    input.value = ''
}

// upload
const uploadImage = async (file: File) => {
    loading.value = true

    const formData = new FormData()
    formData.append('image', file)
    formData.append('current_url', props.modelValue ?? '')
    if (props.width) {
        formData.append('width', props.width)
    }
    if (props.height) {
        formData.append('height', props.height)
    }
    if (props.format) {
        formData.append('format', props.format)
    }
    await axios.post(upload.uploadImage().url, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }).then(res => {
        emits('update:modelValue', res.data.url)
    }).catch(e => {
        console.error(e)
    }).finally(() => {
        loading.value = false
    })
}

const removeImage = () => {
    emits('update:modelValue', '')

}
</script>
