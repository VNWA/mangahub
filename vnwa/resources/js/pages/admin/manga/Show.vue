<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Edit,
    BookOpen,
    User,
    Award,
    Calendar,
    Eye,
    Star,
    FileText,
    FolderTree,
    Plus,
} from 'lucide-vue-next';
import mangas from '@/routes/mangas';
import categories from '@/routes/categories';
import authors from '@/routes/authors';
import badges from '@/routes/badges';

interface Props {
    manga: {
        id: number;
        name: string;
        slug: string;
        avatar?: string;
        avatar_url: string;
        description?: string;
        status: string;
        total_views: number;
        total_likes: number;
        total_ratings: number;
        average_rating: number;
        author?: {
            id: number;
            name: string;
            slug: string;
        };
        badge?: {
            id: number;
            name: string;
            slug: string;
            light_text_color: string;
            light_bg_color: string;
        };
        user?: {
            id: number;
            name: string;
        };
        categories?: Array<{
            id: number;
            name: string;
            slug: string;
        }>;
        chapters?: Array<{
            id: number;
            name: string;
            slug: string;
            order: number;
            created_at: string;
        }>;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Mangas', href: mangas.index().url },
    { title: props.manga.name, href: '#' },
];

const statusLabels: Record<string, string> = {
    ongoing: 'Đang tiến hành',
    completed: 'Hoàn thành',
    hiatus: 'Tạm ngưng',
    cancelled: 'Đã hủy',
};

const statusColors: Record<string, string> = {
    ongoing: 'bg-blue-500',
    completed: 'bg-green-500',
    hiatus: 'bg-yellow-500',
    cancelled: 'bg-red-500',
};
</script>

<template>

    <Head :title="manga.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="mangas.index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Quay lại
                    </Link>
                </Button>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight">{{ manga.name }}</h1>
                        <Badge v-if="manga.badge" :style="{
                            color: manga.badge.light_text_color,
                            backgroundColor: manga.badge.light_bg_color,
                        }" class="text-sm font-semibold">
                            {{ manga.badge.name }}
                        </Badge>
                        <Badge :class="statusColors[manga.status] || 'bg-gray-500'" class="text-white">
                            {{ statusLabels[manga.status] || manga.status }}
                        </Badge>
                    </div>
                    <p v-if="manga.description" class="text-muted-foreground mt-2 line-clamp-2">
                        {{ manga.description }}
                    </p>
                </div>
                <Button as-child>
                    <Link :href="mangas.edit(manga.id).url">
                        <Edit class="mr-2 h-4 w-4" />
                        Chỉnh sửa
                    </Link>
                </Button>
            </div>

            <Separator />

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Manga Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Thông tin Manga</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Tác giả</p>
                                    <div v-if="manga.author" class="mt-1">
                                        <Link :href="authors.show(manga.author.id).url"
                                            class="text-sm font-medium hover:underline flex items-center gap-1">
                                            <User class="h-4 w-4" />
                                            {{ manga.author.name }}
                                        </Link>
                                    </div>
                                    <p v-else class="text-sm text-muted-foreground">Chưa có</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Người tạo</p>
                                    <p v-if="manga.user" class="text-sm font-medium mt-1">
                                        {{ manga.user.name }}
                                    </p>
                                    <p v-else class="text-sm text-muted-foreground">Chưa có</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Lượt xem</p>
                                    <p class="text-sm font-medium mt-1 flex items-center gap-1">
                                        <Eye class="h-4 w-4" />
                                        {{ manga.total_views?.toLocaleString() || 0 }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Lượt thích</p>
                                    <p class="text-sm font-medium mt-1 flex items-center gap-1">
                                        <Star class="h-4 w-4" />
                                        {{ manga.total_likes?.toLocaleString() || 0 }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Đánh giá</p>
                                    <p class="text-sm font-medium mt-1">
                                        {{ manga.average_rating?.toFixed(1) || '0.0' }} / 5.0
                                        <span class="text-muted-foreground">
                                            ({{ manga.total_ratings || 0 }} đánh giá)
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Slug</p>
                                    <p class="text-sm font-mono text-muted-foreground mt-1">
                                        {{ manga.slug }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="manga.description" class="pt-4 border-t">
                                <p class="text-sm font-medium text-muted-foreground mb-2">Mô tả</p>
                                <p class="text-sm whitespace-pre-wrap">{{ manga.description }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Categories -->
                    <Card v-if="manga.categories && manga.categories.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FolderTree class="h-5 w-5" />
                                Categories
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <Link v-for="category in manga.categories" :key="category.id"
                                    :href="categories.show(category.id).url">
                                    <Badge variant="outline" class="hover:bg-accent">
                                        {{ category.name }}
                                    </Badge>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Chapters -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="h-5 w-5" />
                                    Chapters
                                    <Badge variant="secondary" class="ml-2">
                                        {{ manga.chapters?.length || 0 }}
                                    </Badge>
                                </CardTitle>
                                <Button size="sm" as-child>
                                    <Link :href="mangas.chapters.index(manga.id).url">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Quản lý Chapters
                                    </Link>
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="manga.chapters && manga.chapters.length > 0" class="space-y-2">
                                <div v-for="chapter in manga.chapters" :key="chapter.id"
                                    class="flex items-center justify-between p-3 rounded-lg border hover:bg-accent/50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-medium text-muted-foreground w-8">
                                            #{{ chapter.order }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-medium">{{ chapter.name }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ new Date(chapter.created_at).toLocaleDateString('vi-VN') }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="mangas.chapters.index(manga.id).url">
                                            Xem
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                <FileText class="h-12 w-12 mx-auto mb-2 opacity-50" />
                                <p>Chưa có chapter nào</p>
                                <Button size="sm" class="mt-4" as-child>
                                    <Link :href="mangas.chapters.index(manga.id).url">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Thêm Chapter đầu tiên
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Avatar -->
                    <Card>
                        <CardContent class="p-0">
                            <div v-if="manga.avatar" class="relative aspect-[3/4] w-full overflow-hidden rounded-lg">
                                <img :src="manga.avatar_url" :alt="manga.name" class="h-full w-full object-cover" />
                            </div>
                            <div v-else
                                class="flex aspect-[3/4] w-full items-center justify-center rounded-lg border-2 border-dashed bg-muted">
                                <BookOpen class="h-16 w-16 text-muted-foreground" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Thao tác nhanh</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button variant="outline" class="w-full justify-start" as-child>
                                <Link :href="mangas.edit(manga.id).url">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Chỉnh sửa Manga
                                </Link>
                            </Button>
                            <Button variant="outline" class="w-full justify-start" as-child>
                                <Link :href="mangas.chapters.index(manga.id).url">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Quản lý Chapters
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
