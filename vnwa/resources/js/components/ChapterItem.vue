<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { GripVertical, Eye, Edit, Trash2, Image as ImageIcon, Calendar, User } from 'lucide-vue-next';
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
    chapter: MangaChapter;
    isReordering?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isReordering: false,
});

const emit = defineEmits<{
    view: [chapter: MangaChapter];
    edit: [chapter: MangaChapter];
    delete: [chapterId: number];
}>();

const getImageCount = (chapter: MangaChapter): number => {
    if (!chapter.server_contents || chapter.server_contents.length === 0) {
        return 0;
    }
    return chapter.server_contents.reduce((total, content) => total + (content.urls?.length || 0), 0);
};

const getFirstImageUrl = (chapter: MangaChapter): string | null => {
    if (!chapter.server_contents || chapter.server_contents.length === 0) {
        return null;
    }
    const firstContent = chapter.server_contents[0];
    const url = firstContent.urls && firstContent.urls.length > 0 ? firstContent.urls[0] : null;
    return url ? getStorageUrl(url) : null;
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
    <div class="group relative flex items-center gap-4 p-4 transition-all hover:bg-muted/30"
        :class="{
            'bg-muted/50': isReordering,
        }">
        <!-- Drag Handle -->
        <div
            class="drag-handle cursor-grab active:cursor-grabbing flex-shrink-0 text-muted-foreground transition-colors hover:text-foreground">
            <GripVertical class="h-5 w-5" />
        </div>

        <!-- Preview Image -->
        <div v-if="getFirstImageUrl(chapter)"
            class="relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border bg-muted">
            <img :src="getFirstImageUrl(chapter)!" :alt="chapter.name"
                class="h-full w-full object-cover" loading="lazy" />
        </div>
        <div v-else
            class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-lg border bg-muted">
            <ImageIcon class="h-6 w-6 text-muted-foreground" />
        </div>

        <!-- Chapter Info -->
        <div class="flex-1 min-w-0 space-y-1">
            <div class="flex items-center gap-2">
                <h3 class="font-semibold truncate">{{ chapter.name }}</h3>
                <Badge variant="secondary" class="text-xs">
                    #{{ chapter.order + 1 }}
                </Badge>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                <div class="flex items-center gap-1">
                    <ImageIcon class="h-3 w-3" />
                    <span>{{ getImageCount(chapter) }} ảnh</span>
                </div>
                <div v-if="chapter.user" class="flex items-center gap-1">
                    <User class="h-3 w-3" />
                    <span>{{ chapter.user.name }}</span>
                </div>
                <div v-if="chapter.created_at" class="flex items-center gap-1">
                    <Calendar class="h-3 w-3" />
                    <span>{{ formatDate(chapter.created_at) }}</span>
                </div>
                <div v-if="chapter.is_locked" class="flex items-center gap-1">
                    <Badge variant="destructive" class="text-xs">
                        🔒 Khóa ({{ chapter.coin_cost || 0 }} coin)
                    </Badge>
                </div>
            </div>
            <p v-if="chapter.description"
                class="line-clamp-1 text-sm text-muted-foreground">
                {{ chapter.description }}
            </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1 flex-shrink-0">
            <Button variant="ghost" size="sm" class="h-8 w-8 p-0"
                @click="emit('view', chapter)" title="Xem chi tiết">
                <Eye class="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" class="h-8 w-8 p-0"
                @click="emit('edit', chapter)" title="Chỉnh sửa">
                <Edit class="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm"
                class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                @click="emit('delete', chapter.id)" title="Xóa">
                <Trash2 class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
