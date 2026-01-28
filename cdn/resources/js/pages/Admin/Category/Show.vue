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
    FolderTree,
    BookOpen,
    FileText,
} from 'lucide-vue-next';
import categories from '@/routes/categories';
import mangas from '@/routes/mangas';

interface Props {
    category: {
        id: number;
        name: string;
        slug: string;
        icon?: string;
        avatar?: string;
        description?: string;
        mangas_count?: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Categories', href: categories.index().url },
    { title: props.category.name, href: '#' },
];
</script>

<template>
    <Head :title="category.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="categories.index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Quay lại
                    </Link>
                </Button>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <span v-if="category.icon" class="text-3xl">{{ category.icon }}</span>
                        <h1 class="text-3xl font-bold tracking-tight">{{ category.name }}</h1>
                        <Badge variant="secondary" class="text-sm">
                            {{ category.mangas_count || 0 }} manga
                        </Badge>
                    </div>
                    <p v-if="category.description" class="text-muted-foreground mt-2">
                        {{ category.description }}
                    </p>
                </div>
                <Button as-child>
                    <Link :href="categories.edit(category.id).url">
                        <Edit class="mr-2 h-4 w-4" />
                        Chỉnh sửa
                    </Link>
                </Button>
            </div>

            <Separator />

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Thông tin Category</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Tên</p>
                                    <p class="text-sm font-medium mt-1">{{ category.name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Slug</p>
                                    <p class="text-sm font-mono text-muted-foreground mt-1">
                                        {{ category.slug }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Icon</p>
                                    <p class="text-2xl mt-1">{{ category.icon || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Số lượng Manga</p>
                                    <p class="text-sm font-medium mt-1 flex items-center gap-1">
                                        <BookOpen class="h-4 w-4" />
                                        {{ category.mangas_count || 0 }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="category.description" class="pt-4 border-t">
                                <p class="text-sm font-medium text-muted-foreground mb-2">Mô tả</p>
                                <p class="text-sm whitespace-pre-wrap">{{ category.description }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BookOpen class="h-5 w-5" />
                                Mangas trong Category
                            </CardTitle>
                            <CardDescription>
                                Danh sách các manga thuộc category này
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-center py-8 text-muted-foreground">
                                <BookOpen class="h-12 w-12 mx-auto mb-2 opacity-50" />
                                <p>Xem danh sách manga trong trang quản lý Manga</p>
                                <Button size="sm" class="mt-4" as-child>
                                    <Link :href="mangas.index().url">
                                        <BookOpen class="mr-2 h-4 w-4" />
                                        Xem tất cả Manga
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
                            <div v-if="category.avatar" class="relative aspect-square w-full overflow-hidden rounded-lg">
                                <img
                                    :src="category.avatar"
                                    :alt="category.name"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div
                                v-else
                                class="flex aspect-square w-full items-center justify-center rounded-lg border-2 border-dashed bg-muted"
                            >
                                <FolderTree class="h-16 w-16 text-muted-foreground" />
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
                                <Link :href="categories.edit(category.id).url">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Chỉnh sửa Category
                                </Link>
                            </Button>
                            <Button variant="outline" class="w-full justify-start" as-child>
                                <Link :href="mangas.index().url">
                                    <BookOpen class="mr-2 h-4 w-4" />
                                    Xem tất cả Manga
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
