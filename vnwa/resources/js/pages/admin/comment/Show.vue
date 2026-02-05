<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { computed } from 'vue';
import { ArrowLeft, Pin,  Trash2, MessageSquare, User, Calendar } from 'lucide-vue-next';
import { dashboard } from '@/routes';

const toast = useToast();

interface Comment {
    id: number;
    content: string;
    is_pinned: boolean;
    is_edited: boolean;
    likes_count: number;
    dislikes_count: number;
    replies_count: number;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
        avatar?: string;
    };
    page?: {
        pageable_type: string;
        pageable?: {
            id: number;
            name?: string;
            title?: string;
            slug?: string;
        };
    };
    parent?: {
        id: number;
        content: string;
        user_id: number;
    };
    replies?: Array<{
        id: number;
        content: string;
        created_at: string;
        user?: {
            id: number;
            name: string;
            email: string;
            avatar?: string;
        };
    }>;
}

interface Props {
    comment: Comment;
}

const props = defineProps<Props>();

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Comments',
        href: '/admin/comments',
    },
    {
        title: 'Chi tiết',
        href: '#',
    },
]);

const handleDelete = () => {
    if (!confirm('Bạn có chắc chắn muốn xóa comment này?')) {
        return;
    }

    router.delete(`/admin/comments/${props.comment.id}`, {
        onSuccess: () => {
            toast.success('Comment đã được xóa thành công.');
            router.visit('/admin/comments');
        },
        onError: (errors) => {
            toast.error(errors.message || 'Có lỗi xảy ra khi xóa comment.');
        },
    });
};

const handlePin = () => {
    router.post(`/admin/comments/${props.comment.id}/pin`, {
        onSuccess: () => {
            toast.success('Comment đã được ghim.');
        },
    });
};

const handleUnpin = () => {
    router.post(`/admin/comments/${props.comment.id}/unpin`, {
        onSuccess: () => {
            toast.success('Comment đã được bỏ ghim.');
        },
    });
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
    <Head title="Chi tiết Comment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/admin/comments">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold tracking-tight">Chi tiết Comment</h1>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="!comment.is_pinned"
                        variant="outline"
                        @click="handlePin"
                    >
                        <Pin class="mr-2 h-4 w-4" />
                        Ghim
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        @click="handleUnpin"
                    >
                        <!-- <Unpin class="mr-2 h-4 w-4" /> -->
                        Bỏ ghim
                    </Button>
                    <Button variant="destructive" @click="handleDelete">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Xóa
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Comment Content -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>Nội dung Comment</CardTitle>
                            <div class="flex gap-2">
                                <Badge v-if="comment.is_pinned" variant="default">
                                    <Pin class="mr-1 h-3 w-3" />
                                    Pinned
                                </Badge>
                                <Badge v-if="comment.is_edited" variant="outline">
                                    Đã chỉnh sửa
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="rounded-lg border bg-muted/50 p-4">
                                <p class="whitespace-pre-wrap">{{ comment.content }}</p>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4" />
                                    <span>Tạo: {{ new Date(comment.created_at).toLocaleString('vi-VN') }}</span>
                                </div>
                                <div v-if="comment.updated_at !== comment.created_at" class="flex items-center gap-2">
                                    <span>Cập nhật: {{ new Date(comment.updated_at).toLocaleString('vi-VN') }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- User Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Người dùng</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="comment.user" class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-full border bg-muted">
                                    <img
                                        v-if="comment.user.avatar"
                                        :src="comment.user.avatar"
                                        :alt="comment.user.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full items-center justify-center"
                                    >
                                        <User class="h-8 w-8 text-muted-foreground" />
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold">{{ comment.user.name }}</h3>
                                    <p class="text-sm text-muted-foreground">{{ comment.user.email }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            Không có thông tin người dùng
                        </div>
                    </CardContent>
                </Card>

                <!-- Page Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Liên kết</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="comment.page" class="space-y-2">
                            <div>
                                <Badge variant="outline">
                                    {{ getPageableTypeLabel(comment.page.pageable_type) }}
                                </Badge>
                            </div>
                            <div v-if="comment.page.pageable" class="text-sm">
                                <div class="font-medium">
                                    {{ comment.page.pageable.name || comment.page.pageable.title }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            Không có thông tin liên kết
                        </div>
                    </CardContent>
                </Card>

                <!-- Stats -->
                <Card>
                    <CardHeader>
                        <CardTitle>Thống kê</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <div class="text-sm text-muted-foreground">Likes</div>
                                <div class="text-2xl font-bold text-green-600">{{ comment.likes_count }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-sm text-muted-foreground">Dislikes</div>
                                <div class="text-2xl font-bold text-red-600">{{ comment.dislikes_count }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-sm text-muted-foreground">Replies</div>
                                <div class="text-2xl font-bold">{{ comment.replies_count }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Parent Comment -->
                <Card v-if="comment.parent">
                    <CardHeader>
                        <CardTitle>Comment cha</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-lg border bg-muted/50 p-3">
                            <p class="text-sm line-clamp-3">{{ comment.parent.content }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Replies -->
                <Card v-if="comment.replies && comment.replies.length > 0" class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Replies ({{ comment.replies.length }})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="reply in comment.replies"
                                :key="reply.id"
                                class="rounded-lg border p-3"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        v-if="reply.user"
                                        class="relative h-8 w-8 shrink-0 overflow-hidden rounded-full border bg-muted"
                                    >
                                        <img
                                            v-if="reply.user.avatar"
                                            :src="reply.user.avatar"
                                            :alt="reply.user.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full items-center justify-center"
                                        >
                                            <User class="h-4 w-4 text-muted-foreground" />
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium">
                                                {{ reply.user?.name || 'Unknown' }}
                                            </span>
                                            <span class="text-xs text-muted-foreground">
                                                {{ new Date(reply.created_at).toLocaleString('vi-VN') }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-sm">{{ reply.content }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
