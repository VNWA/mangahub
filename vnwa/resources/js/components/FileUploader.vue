<template>
    <div class="w-full">
        <FilePond ref="filepondRef" :class-name="className" :allow-multiple="true" :max-files="maxFiles"
            :max-file-size="maxFileSize" :server="serverConfig" @init="handleInit" @processfile="handleProcessFile"
            @processfileerror="handleProcessFileError" @removefile="handleRemoveFile"
            @processfiles="handleProcessFiles" />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import vueFilePond, { setOptions } from 'vue-filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.esm.js';
import 'filepond/dist/filepond.min.css';
import files from '@/routes/files';
import { usePage } from '@inertiajs/vue3';

interface Props {
    path?: string;
    maxFiles?: number;
    maxFileSize?: string;
    className?: string;
    csrfToken?: string;
}

interface Emits {
    (e: 'uploaded', files: string[]): void;
    (e: 'error', error: string): void;
    (e: 'all-complete', files: string[]): void;
}

const props = withDefaults(defineProps<Props>(), {
    path: '',
    maxFiles: 50,
    maxFileSize: '500MB',
    className: 'file-uploader',
    csrfToken: '',
});

const emit = defineEmits<Emits>();

const filepondRef = ref<any>(null);
const processedFiles = ref<Map<string, string>>(new Map()); // fileId -> serverId

const FilePond = vueFilePond(FilePondPluginFileValidateType);
const page = usePage();
const csrfToken = props.csrfToken || page.props.csrf_token || '';

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
        labelIdle: 'Kéo thả files hoặc <span class="filepond--label-action">Chọn files</span>',
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
        const response = await fetch(files.uploadFilepond().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                server_ids: serverIds,
                path: props.path,
            }),
        });

        const data = await response.json();

        if (data.success) {
            emit('uploaded', data.files || []);
            emit('all-complete', data.files || []);
            processedFiles.value.clear();
            setTimeout(() => filepondRef.value?.removeFiles(), 1000);
        } else {
            emit('error', data.message || 'Lỗi khi upload files');
        }
    } catch (error: any) {
        emit('error', error.message || 'Lỗi khi xử lý files');
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
.file-uploader {
    width: 100%;
}
</style>
