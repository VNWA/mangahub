<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Eye, Edit, Image as ImageIcon } from 'lucide-vue-next';
import { getStorageUrl } from '@/utils/storage';

interface MangaChapter {
    id: number;
    name: string;
    slug: string;
    description?: string;
    order: number;
    coin_cost?: number;
    is_locked?: boolean;
    created_at?: string;
    user?: {
        id: number;
        name: string;
    };
    server_contents?: Array<{
        id: number;
        manga_server_id?: number;
        urls: string[];
        server?: {
            id: number;
            name: string;
        };
    }>;
}

interface Props {
    open: boolean;
    chapter: MangaChapter | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    edit: [chapter: MangaChapter];
}>();

const getImageCount = (chapter: MangaChapter): number => {
    if (!chapter.server_contents || chapter.server_contents.length === 0) {
        return 0;
    }
    return chapter.server_contents.reduce((total, content) => total + (content.urls?.length || 0), 0);
};

const getAllImageUrls = (chapter: MangaChapter): string[] => {
    if (!chapter.server_contents || chapter.server_contents.length === 0) {
        return [];
    }
    const allUrls: string[] = [];
    chapter.server_contents.forEach((content) => {
        if (content.urls && content.urls.length > 0) {
            content.urls.forEach((url) => {
                const storageUrl = getStorageUrl(url);
                if (storageUrl) {
                    allUrls.push(storageUrl);
                }
            });
        }
    });
    return allUrls;
};

const formatDate = (dateString?: string): string => {
    if (!dateString) {
        return '';
    }
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Eye class="h-5 w-5" />
                    Chi tiết Chapter
                </DialogTitle>
                <DialogDescription v-if="chapter">
                    {{ chapter.name }}
                </DialogDescription>
            </DialogHeader>
            <div v-if="chapter" class="space-y-6 py-4">
                <!-- Chapter Info -->
                <div class="space-y-4">
                    <div>
                        <Label class="text-xs text-muted-foreground">Tên Chapter</Label>
                        <p class="mt-1 font-semibold">{{ chapter.name }}</p>
                    </div>
                    <div v-if="chapter.description">
                        <Label class="text-xs text-muted-foreground">Mô tả</Label>
                        <p class="mt-1 text-sm">{{ chapter.description }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label class="text-xs text-muted-foreground">Thứ tự</Label>
                            <p class="mt-1 font-semibold">#{{ chapter.order + 1 }}</p>
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground">Số ảnh</Label>
                            <p class="mt-1 font-semibold">{{ getImageCount(chapter) }} ảnh</p>
                        </div>
                        <div v-if="chapter.user">
                            <Label class="text-xs text-muted-foreground">Người tạo</Label>
                            <p class="mt-1 text-sm">{{ chapter.user.name }}</p>
                        </div>
                        <div v-if="chapter.created_at">
                            <Label class="text-xs text-muted-foreground">Ngày tạo</Label>
                            <p class="mt-1 text-sm">{{ formatDate(chapter.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Images Grid -->
                <div v-if="getAllImageUrls(chapter).length > 0">
                    <Label class="text-xs text-muted-foreground">Danh sách ảnh</Label>
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        <div v-for="(url, index) in getAllImageUrls(chapter)" :key="index"
                            class="group relative aspect-square overflow-hidden rounded-lg border bg-muted">
                            <img :src="url" :alt="`Image ${index + 1}`"
                                class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                loading="lazy" />
                            <div
                                class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                <Badge variant="secondary" class="text-xs">
                                    {{ index + 1 }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-lg border border-dashed p-8 text-center">
                    <ImageIcon class="mx-auto h-12 w-12 text-muted-foreground" />
                    <p class="mt-2 text-sm text-muted-foreground">Chưa có ảnh nào trong chapter này</p>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">Đóng</Button>
                <Button v-if="chapter" @click="
                    emit('edit', chapter);
                    emit('update:open', false);
                ">
                    <Edit class="mr-2 h-4 w-4" />
                    Chỉnh sửa
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
