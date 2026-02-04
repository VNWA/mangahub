<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Edit, Plus, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface ServerContent {
    id?: number;
    manga_server_id?: number;
    urls: string;
    server_name?: string;
}

interface Props {
    open: boolean;
    chapter: {
        id: number;
        name: string;
        description?: string;
        coin_cost?: number;
        is_locked?: boolean;
        server_contents?: Array<{
            id: number;
            manga_server_id?: number;
            urls: string[];
            server?: {
                id: number;
                name: string;
            };
        }>;
    } | null;
    servers: Array<{
        id: number;
        name: string;
    }>;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    submit: [data: {
        name: string;
        description: string;
        coin_cost: number;
        is_locked: boolean;
        server_contents: Array<{
            id?: number;
            manga_server_id?: number;
            urls: string;
        }>;
    }];
}>();

const form = ref({
    name: '',
    description: '',
    coin_cost: 0,
    is_locked: false,
    server_contents: [] as ServerContent[],
});

watch(() => props.chapter, (chapter) => {
    if (chapter) {
        const serverContents = chapter.server_contents?.map((content) => {
            const server = props.servers.find((s) => s.id === content.server?.id);
            return {
                id: content.id,
                manga_server_id: server?.id || content.server?.id || undefined,
                urls: Array.isArray(content.urls) ? content.urls.join('\n') : '',
                server_name: server?.name || content.server?.name || 'Unknown Server',
            };
        }) || [];

        if (serverContents.length === 0) {
            serverContents.push({
                manga_server_id: props.servers[0]?.id || undefined,
                urls: '',
                server_name: props.servers[0]?.name || 'Default Server',
            });
        }

        form.value = {
            name: chapter.name,
            description: chapter.description || '',
            coin_cost: chapter.coin_cost || 0,
            is_locked: chapter.is_locked || false,
            server_contents: serverContents,
        };
    }
}, { immediate: true });

const handleSubmit = () => {
    const serverContents = form.value.server_contents
        .filter((content) => content.urls.trim())
        .map((content) => ({
            id: content.id,
            manga_server_id: content.manga_server_id,
            urls: content.urls,
        }));

    emit('submit', {
        name: form.value.name,
        description: form.value.description,
        coin_cost: form.value.coin_cost,
        is_locked: form.value.is_locked,
        server_contents: serverContents,
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Edit class="h-5 w-5" />
                    Chỉnh sửa Chapter
                </DialogTitle>
                <DialogDescription>
                    Cập nhật thông tin chapter và nội dung server.
                </DialogDescription>
            </DialogHeader>
            <div class="flex flex-col gap-4 py-4">
                <!-- Chapter Info -->
                <div class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <Label for="edit-name">Tên Chapter *</Label>
                        <Input id="edit-name" v-model="form.name" placeholder="Ví dụ: Chapter 1" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label for="edit-description">Mô tả (tùy chọn)</Label>
                        <textarea id="edit-description" v-model="form.description"
                            class="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                            placeholder="Mô tả chapter" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="edit-coin-cost">Coin Cost</Label>
                            <Input id="edit-coin-cost" v-model.number="form.coin_cost" type="number" min="0"
                                placeholder="0" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="edit-is-locked">Trạng thái</Label>
                            <div class="flex items-center gap-2">
                                <input id="edit-is-locked" v-model="form.is_locked" type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300" />
                                <Label for="edit-is-locked" class="cursor-pointer">
                                    Khóa chapter (cần coin để mở)
                                </Label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Server Contents Section -->
                <Separator />
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <Label class="text-base font-semibold">Nội dung Server</Label>
                        <Button type="button" variant="outline" size="sm" @click="
                            form.server_contents.push({
                                manga_server_id: servers[0]?.id || undefined,
                                urls: '',
                                server_name: servers[0]?.name || 'New Server',
                            })
                        ">
                            <Plus class="mr-2 h-4 w-4" />
                            Thêm Server
                        </Button>
                    </div>

                    <!-- Server Content Items -->
                    <div v-for="(content, index) in form.server_contents" :key="index"
                        class="space-y-3 rounded-lg border p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Label class="font-medium">Server:</Label>
                                <Select v-model="content.manga_server_id" :options="[
                                    { value: undefined, label: 'Chọn server' },
                                    ...servers.map((server) => ({
                                        value: server.id,
                                        label: server.name,
                                    })),
                                ]" @update:modelValue="
                                    const server = servers.find((s) => s.id === content.manga_server_id);
                                    if (server) {
                                        content.server_name = server.name;
                                    } else {
                                        content.server_name = 'Unknown Server';
                                    }
                                " />
                                <span v-if="content.server_name" class="text-xs text-muted-foreground">
                                    ({{ content.server_name }})
                                </span>
                            </div>
                            <Button v-if="form.server_contents.length > 1" type="button" variant="ghost"
                                size="sm" @click="form.server_contents.splice(index, 1)">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>

                        <div class="space-y-2">
                            <Label :for="`edit-urls-${index}`">
                                URLs (mỗi URL một dòng hoặc cách nhau bằng khoảng trắng)
                            </Label>
                            <textarea :id="`edit-urls-${index}`" v-model="content.urls"
                                class="min-h-[150px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm font-mono shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] placeholder:text-muted-foreground"
                                placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg" />
                            <p class="text-xs text-muted-foreground">
                                {{ content.urls.split(/[\s\n\r]+/).filter((url: string) => url.trim()).length }}
                                URL(s) hợp lệ
                            </p>
                        </div>
                    </div>

                    <div v-if="form.server_contents.length === 0"
                        class="rounded-lg border border-dashed p-8 text-center">
                        <p class="text-sm text-muted-foreground">Chưa có server content nào. Click "Thêm Server"
                            để thêm.
                        </p>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)" :disabled="loading">
                    Hủy
                </Button>
                <Button @click="handleSubmit" :disabled="loading">
                    <Spinner v-if="loading" class="mr-2 h-4 w-4" />
                    {{ loading ? 'Đang cập nhật...' : 'Cập nhật' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
