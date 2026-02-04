<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { LinkIcon } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    open: boolean;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    submit: [data: { name: string; urls: string; description: string }];
}>();

const form = ref({
    name: '',
    urls: '',
    description: '',
});

const handleSubmit = () => {
    if (!form.value.name.trim() || !form.value.urls.trim()) {
        return;
    }
    emit('submit', {
        name: form.value.name,
        urls: form.value.urls,
        description: form.value.description,
    });
    form.value = {
        name: '',
        urls: '',
        description: '',
    };
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-[650px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <LinkIcon class="h-5 w-5" />
                    Thêm Chapter từ URLs
                </DialogTitle>
                <DialogDescription>
                    Nhập tên chapter và danh sách URLs ảnh. Mỗi URL một dòng hoặc cách nhau bằng khoảng
                    trắng.
                </DialogDescription>
            </DialogHeader>
            <div class="flex flex-col gap-4 py-4">
                <div class="flex flex-col gap-2">
                    <Label for="urls-name">Tên Chapter *</Label>
                    <Input id="urls-name" v-model="form.name" placeholder="Ví dụ: Chapter 1" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="urls-textarea">Danh sách URLs *</Label>
                    <textarea id="urls-textarea" v-model="form.urls"
                        class="min-h-[250px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                        placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg" />
                    <p class="text-xs text-muted-foreground">
                        Mỗi URL một dòng hoặc cách nhau bằng khoảng trắng
                    </p>
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="urls-description">Mô tả (tùy chọn)</Label>
                    <Input id="urls-description" v-model="form.description"
                        placeholder="Mô tả chapter" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)" :disabled="loading">
                    Hủy
                </Button>
                <Button @click="handleSubmit" :disabled="loading || !form.name.trim() || !form.urls.trim()">
                    <Spinner v-if="loading" class="mr-2 h-4 w-4" />
                    {{ loading ? 'Đang thêm...' : 'Thêm' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
