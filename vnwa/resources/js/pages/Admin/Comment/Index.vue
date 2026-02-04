<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref, computed } from 'vue';
import { Search, X, Eye, Trash2, MessageSquare, Pin, MoreVertical } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard } from '@/routes';

const toast = useToast();

interface Comment {
    id: number;
    content: string;
    is_pinned: boolean;
    likes_count: number;
    dislikes_count: number;
    replies_count: number;
    created_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
    page?: {
        pageable_type: string;
        pageable?: {
            id: number;
            name?: string;
            title?: string;
        };
    };
}

interface Props {
    comments: {
        data: Comment[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
        is_pinned?: string;
        pageable_type?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const isPinnedFilter = ref(props.filters.is_pinned || '');
const pageableTypeFilter = ref(props.filters.pageable_type || '');

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Comments',
        href: '#',
    },
]);

const handleSearch = () => {
    router.get(
        '/admin/comments',
        {
            search: searchQuery.value || undefined,
            is_pinned: isPinnedFilter.value || undefined,
            pageable_type: pageableTypeFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const handleDelete = (commentId: number) => {
    if (!confirm('Bạn có chắc chắn muốn xóa comment này?')) {
        return;
    }

    router.delete(`/admin/comments/${commentId}`, {
        onSuccess: () => {
            toast.success('Comment đã được xóa thành công.');
        },
        onError: (errors) => {
            toast.error(errors.message || 'Có lỗi xảy ra khi xóa comment.');
        },
    });
};

const handlePin = (commentId: number) => {
    router.post(`/admin/comments/${commentId}/pin`, {
        onSuccess: () => {
            toast.success('Comment đã được ghim.');
        },
    });
};

const handleUnpin = (commentId: number) => {
    router.post(`/admin/comments/${commentId}/unpin`, {
        onSuccess: () => {
            toast.success('Comment đã được bỏ ghim.');
        },
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    isPinnedFilter.value = '';
    pageableTypeFilter.value = '';
    router.get('/admin/comments', {}, { preserveState: true, replace: true });
};

const getPageableTypeLabel = (type: string): string => {
    const labels: Record<string, string> = {
        'App\\Models\\Manga': 'Manga',
        'App\\Models\\MangaChapter': 'Chapter',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head title="Quản lý Comments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">Quản lý Comments</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ props.comments.total }} comments
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">Quản lý tất cả comments trong hệ thống</p>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Tìm kiếm comment..."
                                class="pl-9 pr-9"
                                @keyup.enter="handleSearch"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <Select
                                v-model="isPinnedFilter"
                                :options="[
                                    { value: '', label: 'Tất cả' },
                                    { value: 'true', label: 'Đã ghim' },
                                    { value: 'false', label: 'Chưa ghim' },
                                ]"
                                @update:modelValue="handleSearch"
                            />
                            <Button variant="outline" @click="handleSearch">
                                <Search class="mr-2 h-4 w-4" />
                                Tìm
                            </Button>
                            <Button
                                v-if="searchQuery || isPinnedFilter || pageableTypeFilter"
                                variant="outline"
                                @click="clearFilters"
                            >
                                <X class="mr-2 h-4 w-4" />
                                Xóa
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Comments Table -->
            <div v-if="props.comments.data.length > 0">
                <Card>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[300px]">
                                            Nội dung
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[150px]">
                                            Người dùng
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground min-w-[150px]">
                                            Liên kết
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-24">
                                            Likes
                                        </th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-24">
                                            Replies
                                        </th>
                                        <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground w-32">
                                            Thao tác
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="comment in props.comments.data"
                                        :key="comment.id"
                                        class="border-b transition-colors hover:bg-muted/50"
                                    >
                                        <td class="p-4 align-middle">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm line-clamp-2">{{ comment.content }}</p>
                                                    <Badge v-if="comment.is_pinned" variant="default" class="text-xs">
                                                        <Pin class="mr-1 h-3 w-3" />
                                                        Pinned
                                                    </Badge>
                                                </div>
                                                <p class="text-xs text-muted-foreground">
                                                    {{ new Date(comment.created_at).toLocaleString('vi-VN') }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div v-if="comment.user" class="text-sm">
                                                <div class="font-medium">{{ comment.user.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ comment.user.email }}</div>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div v-if="comment.page" class="text-sm">
                                                <Badge variant="outline" class="text-xs">
                                                    {{ getPageableTypeLabel(comment.page.pageable_type) }}
                                                </Badge>
                                                <div v-if="comment.page.pageable" class="mt-1 text-xs text-muted-foreground">
                                                    {{ comment.page.pageable.name || comment.page.pageable.title }}
                                                </div>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">—</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm">{{ comment.likes_count }}</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm">{{ comment.replies_count }}</span>
                                        </td>
                                        <td class="p-4 align-middle text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <Button variant="outline" size="sm" as-child>
                                                    <Link :href="`/admin/comments/${comment.id}`">
                                                        <Eye class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger as-child>
                                                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                            <MoreVertical class="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem as-child>
                                                            <Link :href="`/admin/comments/${comment.id}`">
                                                                <Eye class="mr-2 h-4 w-4" />
                                                                Xem chi tiết
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            v-if="!comment.is_pinned"
                                                            @click="handlePin(comment.id)"
                                                        >
                                                            <Pin class="mr-2 h-4 w-4" />
                                                            Ghim
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            v-else
                                                            @click="handleUnpin(comment.id)"
                                                        >
                                                            <Pin class="mr-2 h-4 w-4" />
                                                            Bỏ ghim
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            class="text-destructive"
                                                            @click="handleDelete(comment.id)"
                                                        >
                                                            <Trash2 class="mr-2 h-4 w-4" />
                                                            Xóa
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="props.comments.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
                    <template v-for="(link, index) in props.comments.links" :key="index">
                        <Button
                            v-if="link.url"
                            variant="outline"
                            size="sm"
                            :disabled="link.active"
                            @click="router.get(link.url!)"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                        >
                            <span v-html="link.label" />
                        </Button>
                        <span v-else class="px-2 text-muted-foreground">
                            <span v-html="link.label" />
                        </span>
                    </template>
                </div>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <CardContent class="flex flex-col items-center justify-center py-16">
                    <div class="rounded-full bg-muted p-6">
                        <MessageSquare class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="mt-4 space-y-2 text-center">
                        <h3 class="text-lg font-semibold">Chưa có comment nào</h3>
                        <p class="text-sm text-muted-foreground">
                            Không tìm thấy comment nào phù hợp với bộ lọc
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
