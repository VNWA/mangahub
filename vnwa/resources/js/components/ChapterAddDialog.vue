<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Plus } from 'lucide-vue-next';
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
    submit: [data: { name: string; description: string }];
}>();

const form = ref({
    name: '',
    description: '',
});

const handleSubmit = () => {
    if (!form.value.name.trim()) {
        return;
    }
    emit('submit', {
        name: form.value.name,
        description: form.value.description,
    });
    form.value = {
        name: '',
        description: '',
    };
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-[550px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Plus class="h-5 w-5" />
                    Thêm Chapter mới
                </DialogTitle>
                <DialogDescription>
                    Tạo một chapter mới. Bạn có thể thêm nội dung (ảnh) sau.
                </DialogDescription>
            </DialogHeader>
            <div class="flex flex-col gap-4 py-4">
                <div class="flex flex-col gap-2">
                    <Label for="chapter-name">Tên Chapter *</Label>
                    <Input id="chapter-name" v-model="form.name"
                        placeholder="Ví dụ: Chapter 1" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="chapter-description">Mô tả (tùy chọn)</Label>
                    <textarea id="chapter-description" v-model="form.description"
                        class="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                        placeholder="Mô tả chapter" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)" :disabled="loading">
                    Hủy
                </Button>
                <Button @click="handleSubmit" :disabled="loading || !form.name.trim()">
                    <Spinner v-if="loading" class="mr-2 h-4 w-4" />
                    {{ loading ? 'Đang thêm...' : 'Thêm' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
