<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import mangas from '@/routes/mangas';
import categories from '@/routes/categories';
import authors from '@/routes/authors';
import badges from '@/routes/badges';
import servers from '@/routes/servers';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    FileText,
    FolderTree,
    User,
    Award,
    Server,
    Users,
    Eye,
    Heart,
    TrendingUp,
    Clock,
    Star,
} from 'lucide-vue-next';

interface Props {
    stats: {
        total_mangas: number;
        total_chapters: number;
        total_categories: number;
        total_authors: number;
        total_badges: number;
        total_servers: number;
        total_users: number;
        total_views: number;
        total_follows: number;
    };
    recentMangas: Array<{
        id: number;
        name: string;
        slug: string;
        avatar?: string;
        status: string;
        total_views: number;
        author?: {
            id: number;
            name: string;
        };
        badge?: {
            id: number;
            name: string;
        };
    }>;
    topMangas: Array<{
        id: number;
        name: string;
        slug: string;
        avatar?: string;
        total_views: number;
        author?: {
            id: number;
            name: string;
        };
    }>;
    mangasByStatus: Record<string, number>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const statCards = [
    {
        title: 'Tổng Manga',
        value: props.stats.total_mangas,
        icon: BookOpen,
        color: 'text-blue-500',
        bgColor: 'bg-blue-500/10',
        href: mangas.index().url,
    },
    {
        title: 'Tổng Chapters',
        value: props.stats.total_chapters,
        icon: FileText,
        color: 'text-green-500',
        bgColor: 'bg-green-500/10',
    },
    {
        title: 'Categories',
        value: props.stats.total_categories,
        icon: FolderTree,
        color: 'text-purple-500',
        bgColor: 'bg-purple-500/10',
        href: categories.index().url,
    },
    {
        title: 'Authors',
        value: props.stats.total_authors,
        icon: User,
        color: 'text-orange-500',
        bgColor: 'bg-orange-500/10',
        href: authors.index().url,
    },
    {
        title: 'Badges',
        value: props.stats.total_badges,
        icon: Award,
        color: 'text-yellow-500',
        bgColor: 'bg-yellow-500/10',
        href: badges.index().url,
    },
    {
        title: 'Servers',
        value: props.stats.total_servers,
        icon: Server,
        color: 'text-red-500',
        bgColor: 'bg-red-500/10',
        href: servers.index().url,
    },
    {
        title: 'Users',
        value: props.stats.total_users,
        icon: Users,
        color: 'text-indigo-500',
        bgColor: 'bg-indigo-500/10',
    },
    {
        title: 'Tổng Views',
        value: props.stats.total_views.toLocaleString('vi-VN'),
        icon: Eye,
        color: 'text-cyan-500',
        bgColor: 'bg-cyan-500/10',
    },
];

const formatNumber = (num: number): string => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
};

const getStatusBadgeVariant = (status: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    const variants: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        ongoing: 'default',
        completed: 'secondary',
        hiatus: 'outline',
        cancelled: 'destructive',
    };
    return variants[status] || 'outline';
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        ongoing: 'Đang ra',
        completed: 'Hoàn thành',
        hiatus: 'Tạm ngưng',
        cancelled: 'Hủy',
    };
    return labels[status] || status;
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p class="text-muted-foreground mt-1">Tổng quan hệ thống quản lý truyện tranh</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Link v-for="stat in statCards" :key="stat.title" :href="stat.href || '#'"
                    :class="stat.href ? 'cursor-pointer' : 'cursor-default'">
                    <Card class="transition-all hover:shadow-md" :class="stat.href ? 'hover:border-primary' : ''">
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-muted-foreground">{{ stat.title }}</p>
                                    <p class="text-2xl font-bold">{{ stat.value }}</p>
                                </div>
                                <div :class="['rounded-lg p-3', stat.bgColor]">
                                    <component :is="stat.icon" :class="['h-6 w-6', stat.color]" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <!-- Charts and Lists -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Recent Mangas -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Clock class="h-5 w-5" />
                            Manga mới nhất
                        </CardTitle>
                        <CardDescription>5 manga được thêm gần đây nhất</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentMangas.length > 0" class="space-y-3">
                            <Link v-for="manga in recentMangas" :key="manga.id" :href="mangas.show(manga.id).url"
                                class="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50 cursor-pointer">
                                <div v-if="manga.avatar"
                                    class="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg border bg-muted">
                                    <img :src="manga.avatar" :alt="manga.name" class="h-full w-full object-cover" />
                                </div>
                                <div v-else
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-muted">
                                    <BookOpen class="h-6 w-6 text-muted-foreground" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ manga.name }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Badge :variant="getStatusBadgeVariant(manga.status)" class="text-xs">
                                            {{ getStatusLabel(manga.status) }}
                                        </Badge>
                                        <span v-if="manga.author" class="text-xs text-muted-foreground">
                                            {{ manga.author.name }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                    <Eye class="h-3 w-3" />
                                    <span>{{ formatNumber(manga.total_views) }}</span>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="py-8 text-center text-muted-foreground">
                            <p>Chưa có manga nào</p>
                        </div>
                        <div v-if="recentMangas.length > 0" class="mt-4">
                            <Button variant="outline" class="w-full" as-child>
                                <Link :href="mangas.index().url">Xem tất cả manga</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Mangas -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5" />
                            Top Manga
                        </CardTitle>
                        <CardDescription>5 manga có lượt xem cao nhất</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topMangas.length > 0" class="space-y-3">
                            <Link v-for="(manga, index) in topMangas" :key="manga.id" :href="mangas.show(manga.id).url"
                                class="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50 cursor-pointer">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                    {{ index + 1 }}
                                </div>
                                <div v-if="manga.avatar"
                                    class="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg border bg-muted">
                                    <img :src="manga.avatar" :alt="manga.name" class="h-full w-full object-cover" />
                                </div>
                                <div v-else
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-muted">
                                    <BookOpen class="h-6 w-6 text-muted-foreground" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ manga.name }}</p>
                                    <p v-if="manga.author" class="text-xs text-muted-foreground mt-1">
                                        {{ manga.author.name }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 text-sm font-semibold">
                                    <Eye class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ formatNumber(manga.total_views) }}</span>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="py-8 text-center text-muted-foreground">
                            <p>Chưa có dữ liệu</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Status Overview -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Star class="h-5 w-5" />
                        Thống kê theo trạng thái
                    </CardTitle>
                    <CardDescription>Phân bổ manga theo các trạng thái</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div v-for="(count, status) in mangasByStatus" :key="status"
                            class="rounded-lg border p-4 text-center">
                            <Badge :variant="getStatusBadgeVariant(status)" class="mb-2">
                                {{ getStatusLabel(status) }}
                            </Badge>
                            <p class="text-2xl font-bold mt-2">{{ count }}</p>
                            <p class="text-xs text-muted-foreground mt-1">manga</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
