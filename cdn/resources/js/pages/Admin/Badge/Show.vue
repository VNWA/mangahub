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
    Award,
    BookOpen,
    Palette,
} from 'lucide-vue-next';
import badges from '@/routes/badges';
import mangas from '@/routes/mangas';

interface Props {
    badge: {
        id: number;
        name: string;
        slug: string;
        light_text_color: string;
        light_bg_color: string;
        dark_text_color: string;
        dark_bg_color: string;
        mangas_count?: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Badges', href: badges.index().url },
    { title: props.badge.name, href: '#' },
];
</script>

<template>
    <Head :title="badge.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="badges.index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Quay lại
                    </Link>
                </Button>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <Badge
                            :style="{
                                color: badge.light_text_color,
                                backgroundColor: badge.light_bg_color,
                            }"
                            class="text-lg font-semibold px-3 py-1"
                        >
                            {{ badge.name }}
                        </Badge>
                        <Badge variant="secondary" class="text-sm">
                            {{ badge.mangas_count || 0 }} manga
                        </Badge>
                    </div>
                </div>
                <Button as-child>
                    <Link :href="badges.edit(badge.id).url">
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
                            <CardTitle>Thông tin Badge</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Tên</p>
                                    <p class="text-sm font-medium mt-1">{{ badge.name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Slug</p>
                                    <p class="text-sm font-mono text-muted-foreground mt-1">
                                        {{ badge.slug }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Số lượng Manga</p>
                                    <p class="text-sm font-medium mt-1 flex items-center gap-1">
                                        <BookOpen class="h-4 w-4" />
                                        {{ badge.mangas_count || 0 }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Colors -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Palette class="h-5 w-5" />
                                Màu sắc
                            </CardTitle>
                            <CardDescription>Cấu hình màu sắc của badge</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Light Mode -->
                            <div class="space-y-3">
                                <h4 class="font-semibold">Light Mode</h4>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <p class="text-sm font-medium text-muted-foreground">Màu nền</p>
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-12 w-12 rounded border-2"
                                                :style="{ backgroundColor: badge.light_bg_color }"
                                            />
                                            <code class="text-sm font-mono">{{ badge.light_bg_color }}</code>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-sm font-medium text-muted-foreground">Màu chữ</p>
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-12 w-12 rounded border-2"
                                                :style="{ backgroundColor: badge.light_text_color }"
                                            />
                                            <code class="text-sm font-mono">{{ badge.light_text_color }}</code>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 rounded-lg border bg-white">
                                    <Badge
                                        :style="{
                                            color: badge.light_text_color,
                                            backgroundColor: badge.light_bg_color,
                                        }"
                                        class="text-sm font-semibold"
                                    >
                                        {{ badge.name }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Dark Mode -->
                            <div class="space-y-3">
                                <h4 class="font-semibold">Dark Mode</h4>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <p class="text-sm font-medium text-muted-foreground">Màu nền</p>
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-12 w-12 rounded border-2"
                                                :style="{ backgroundColor: badge.dark_bg_color }"
                                            />
                                            <code class="text-sm font-mono">{{ badge.dark_bg_color }}</code>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-sm font-medium text-muted-foreground">Màu chữ</p>
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-12 w-12 rounded border-2"
                                                :style="{ backgroundColor: badge.dark_text_color }"
                                            />
                                            <code class="text-sm font-mono">{{ badge.dark_text_color }}</code>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 rounded-lg border" style="background-color: #1a1a1a">
                                    <Badge
                                        :style="{
                                            color: badge.dark_text_color,
                                            backgroundColor: badge.dark_bg_color,
                                        }"
                                        class="text-sm font-semibold"
                                    >
                                        {{ badge.name }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BookOpen class="h-5 w-5" />
                                Mangas có Badge này
                            </CardTitle>
                            <CardDescription>
                                Danh sách các manga sử dụng badge này
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
                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Thao tác nhanh</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button variant="outline" class="w-full justify-start" as-child>
                                <Link :href="badges.edit(badge.id).url">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Chỉnh sửa Badge
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
