<template>
    <div class="w-full">
        <FilePond
            ref="filepondRef"
            :class-name="className"
            :allow-multiple="true"
            :accepted-file-types="['application/zip', 'application/x-zip-compressed']"
            :max-files="maxFiles"
            :max-file-size="maxFileSize"
            :server="serverConfig"
            @init="handleInit"
            @processfile="handleProcessFile"
            @processfileerror="handleProcessFileError"
            @removefile="handleRemoveFile"
            @processfiles="handleProcessFiles"
        />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import vueFilePond, { setOptions } from 'vue-filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.esm.js';
import 'filepond/dist/filepond.min.css';
import mangas from '@/routes/mangas';

interface Props {
    mangaId: number;
    maxFiles?: number;
    maxFileSize?: string;
    className?: string;
    csrfToken?: string;
}

interface Emits {
    (e: 'uploaded', chapter: any): void;
    (e: 'error', error: string): void;
    (e: 'all-complete', chapters: any[]): void;
}

const props = withDefaults(defineProps<Props>(), {
    maxFiles: 10,
    maxFileSize: '500MB',
    className: 'chapter-zip-uploader',
    csrfToken: '',
});

const emit = defineEmits<Emits>();

const filepondRef = ref<any>(null);
const processedFiles = ref<Map<string, string>>(new Map()); // fileId -> serverId

const FilePond = vueFilePond(FilePondPluginFileValidateType);

const csrfToken = props.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const serverConfig = {
    process: {
        url: '/filepond',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        onload: (response: string) => {
            try {
                const parsed = JSON.parse(response);
                return parsed.serverId || parsed.id || response;
            } catch {
                return response.trim();
            }
        },
        onerror: (response: string) => {
            try {
                const error = JSON.parse(response);
                return error.message || error.error || 'Upload failed';
            } catch {
                return response || 'Upload failed';
            }
        },
    },
    revert: {
        url: '/filepond',
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    },
};

const handleInit = () => {
    setOptions({
        credits: false,
        labelIdle: 'Kéo thả file ZIP hoặc <span class="filepond--label-action">Chọn file</span>',
    });
};

const handleProcessFile = (error: any, file: any) => {
    if (error) {
        emit('error', error.body || error.message || 'Lỗi khi xử lý file');
        return;
    }
    if (file?.serverId) {
        processedFiles.value.set(file.id, file.serverId);
    }
};

const handleProcessFileError = (response: string) => {
    try {
        const error = JSON.parse(response);
        emit('error', error.message || error.errors?.file?.[0] || error.error || 'Lỗi khi xử lý file');
    } catch {
        emit('error', response || 'Lỗi khi xử lý file');
    }
};

const handleRemoveFile = (_error: any, file: any) => {
    if (file?.id) {
        processedFiles.value.delete(file.id);
    }
};

const handleProcessFiles = async () => {
    const serverIds = Array.from(processedFiles.value.values());
    if (serverIds.length === 0) {
        return;
    }

    try {
        const response = await fetch(mangas.chapters.uploadMultipleZip(props.mangaId).url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ server_ids: serverIds }),
        });

        const data = await response.json();

        if (data.success) {
            data.chapters?.forEach((chapter: any) => emit('uploaded', chapter));
            emit('all-complete', data.chapters || []);
            processedFiles.value.clear();
            setTimeout(() => filepondRef.value?.removeFiles(), 1000);
        } else {
            emit('error', data.message || 'Lỗi khi tạo chapters');
        }
    } catch (error: any) {
        emit('error', error.message || 'Lỗi khi xử lý chapters');
    }
};

defineExpose({
    clear: () => {
        filepondRef.value?.removeFiles();
        processedFiles.value.clear();
    },
});
</script>

<style scoped>
.chapter-zip-uploader {
    width: 100%;
}
</style>
